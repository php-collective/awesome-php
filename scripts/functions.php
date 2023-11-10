<?php
/**
 * This file contains general settings applicable to all scripts in this repository.
 *
 * Note: This file does not contain specific validation rules. Those are defined within each respective "check" script.
 */
// PHP Version required to run this scripts.
define('APHP_REQUIRED_PHP_VERSION', '8.2.0');

// The default GitHub API hostname.
define('APHP_GH_DEFAULT_API_HOSTNAME', 'https://api.github.com');

// The User-Agent header to use when making requests to external resources.
define('APHP_UA', 'Awesome PHP');

// The path to the Awesome PHP README.md file.
define('APHP_NAME_MD', 'README.md');
define('APHP_PATH_MD', __DIR__ . '/../' . APHP_NAME_MD);

// The path where we cache data for the checks
define('APHP_PATH_CACHE', __DIR__ . '/../cache');

/**
 * Quick sanity checks if the current environment supports what we need to run this script.
 *
 * check if the PHP version is at least 8.2, check if json is installed, check if curl is installed etc.
 */
if (version_compare(PHP_VERSION, APHP_REQUIRED_PHP_VERSION, '<')) {
    echo 'This script requires PHP ' . APHP_REQUIRED_PHP_VERSION . ' or newer. You are running ' . PHP_VERSION . PHP_EOL;
    exit(1);
}

foreach (['curl', 'json'] as $extension) {
    if (!extension_loaded($extension)) {
        echo "This script requires the $extension extension. Please install it." . PHP_EOL;
        exit(1);
    }
}

/**
 * This function retrieves the GitHub Personal Access Token (PAT) from the environment variable 'GH_PA_TOKEN'.
 *
 * To set up this environment variable, follow these steps:
 *
 * 1. Generate a new Personal Access Token (PAT) on GitHub:
 *    - Go to your GitHub settings.
 *    - Navigate to Developer settings > Personal access tokens.
 *    - Generate a new token with the appropriate scopes for your use case.
 *
 * 2. Store the PAT in your environment:
 *    - For a local environment, you can set the environment variable in your shell profile file (e.g., .bashrc, .bash_profile, .zshrc).
 *      Add the following line: export GH_PA_TOKEN="your_token_here"
 *    - For a production environment, you might want to use a more secure method, like storing the token in a secret manager or injecting it at deployment time.
 *
 * @throws \Exception
 * @return string The GitHub Personal Access Token.
 */
function getGithubAccessToken(): string
{
    if (!$token = getenv('GH_PA_TOKEN')) {
        throw new Exception('Could not retrieve GitHub Personal Access Token. Please set the environment variable GH_PA_TOKEN.');
    }

    return $token;
}

/**
 * Return a GitHub API hostname, either from the environment variable 'GH_API_HOST' or the default value 'https://api.github.com'.
 *
 * @return string The GitHub API hostname.
 */
function getGithubHostname(): string
{
    return getenv('GH_API_HOST') ?: APHP_GH_DEFAULT_API_HOSTNAME;
}

/**
 * Builds a GitHub API URL from a given path and array of query parameters.
 *
 * @param string $path The path to the GitHub API endpoint.
 * @param array<string, string> $query An array of query parameters.
 *
 * @return string The full GitHub API URL.
 */
function getGithubApiUrl(string $path, array $query = []): string
{
    $url = getGithubHostname() . $path;

    if (count($query) > 0) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

/**
 * Fetches data from the GitHub API.
 *
 * This function sends a GET request to the specified path on the GitHub API, optionally with query parameters.
 *
 * The function will throw an exception if:
 * - The curl request fails.
 * - The HTTP response code is not 200.
 * - The JSON response cannot be decoded.
 * - No data is received.
 *
 * @param string $path The path on the GitHub API to send the request to.
 * @param array $query Optional. An associative array of query parameters to include in the request.
 * @param int $throttle Optional. The number of seconds to wait between requests. Default is 0.
 *
 * @return array The data received from the GitHub API, decoded from JSON into an associative array.
 *
 * @throws \Exception If an error occurs during the request or the response cannot be processed.
 */
function fetchFromGithub(string $path, array $query = [], int $throttle = 0): array
{
    $url = getGithubApiUrl($path, $query);

    $response = retrieveCache('ghresponse.' . $url, function () use ($url, $throttle) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'User-Agent: ' . APHP_UA,
            'Authorization: token ' . getGithubAccessToken(),
        ]);

        curl_setopt($curl, CURLOPT_URL, $url);

        $response = curl_exec($curl);
        if ($response === false) {
            throw new Exception('Could not fetch from GitHub, Curl error: ' . curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            throw new Exception("Could not fetch from GitHub, Unexpected HTTP code: $httpCode");
        }

        curl_close($curl);

        if ($throttle > 0) {
            sleep($throttle);
        }

        return $response;
    }, 60 * 60);

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Could not fetch from GitHub, JSON decode error: ' . json_last_error_msg());
    }

    if ($data === null) {
        throw new Exception('Could not fetch from GitHub, No data received');
    }

    return $data;
}

/**
 * Retruns the path to the cache file for a given identifier.
 *
 * @param string $identifier A unique identifier for the data.
 */
function getCacheFilePath(string $identifier): string
{
    return APHP_PATH_CACHE . '/' . md5($identifier);
}

/**
 * Returns the age of the cache file for a given identifier.
 * We just use the file modification time for this.
 *
 * @param string $identifier A unique identifier for the data.
 */
function getCacheFileAge(string $identifier): int
{
    $path = getCacheFilePath($identifier);
    if (!file_exists($path)) {
        return -1;
    }

    return time() - filemtime($path);
}

/**
 * Retrieves cached data by a unique identifier.
 *
 * This is a simple cache implementation that stores the data serialized in a file.
 *
 * @param string $identifier A unique identifier for the data.
 * @param \Closure $callback A callback function that generates the data if it is not in the cache or if the cache is stale.
 * @param int $maxAge The maximum age of the cache file in seconds. Default is 600 seconds.
 *
 * @return mixed The data retrieved from the cache or generated by the callback function.
 */
function retrieveCache(string $identifier, Closure $callback, int $maxAge = 600): mixed
{
    $path = getCacheFilePath($identifier);
    if (file_exists($path) && getCacheFileAge($identifier) < $maxAge) {
        return unserialize(file_get_contents($path));
    }

    $data = $callback();
    if (!file_exists(APHP_PATH_CACHE)) {
        mkdir(APHP_PATH_CACHE, 0777, true);
    }
    file_put_contents($path, serialize($data));

    return $data;
}

/**
 * Retrieves the contents of the Awesome PHP list markdown file.
 */
function getAwesomeListContents(): string
{
    return file_get_contents(APHP_PATH_MD);
}

/**
 * Finds all URL's of a given host in the given markdown string.
 *
 * Example:
 *   findUrlsInMarkdown('github.com', getAwesomeListContents());
 *
 * @param string $host The host to search for.
 * @param string $markdown The markdown string to search in.
 *
 * @return array<string> An array of URL's.
 */
function findUrlsInMarkdown(string $host, string $markdown): array
{
    preg_match_all("/\((https?:\/\/" . preg_quote($host, '/') . "\/[^\s]+)\)/i", $markdown, $matches);

    return $matches[1];
}

/**
 * Prints a message to stdout in "green" color.
 *
 * @param string $message The message to print.
 */
function printlnGreen(string $message): void
{
    echo "\033[32m$message\033[0m\n";
}

/**
 * Prints a message to stdout in "red" color.
 *
 * @param string $message The message to print.
 */
function printlnRed(string $message): void
{
    echo "\033[31m$message\033[0m\n";
}
