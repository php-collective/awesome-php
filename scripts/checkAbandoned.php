<?php 
require_once __DIR__ . '/functions.php';

/**
 * This check goes through all GitHub repositories in the Awesome PHP list and checks if they are abandoned.
 * 
 * A repository is considered abandoned if it meets the following criteria:
 *  - The repository has not been updated in the last 4 years.
 *  - The repository has been marked as archived.
 */
define('APHP_LAST_PUSH_MAX_AGE', 60 * 60 * 24 * 365 * 4); // 4 years
define('APHP_REQUEST_THROTTLE', 1); // 1 second
define('APHP_GH_ANNOTATION_LEVEL', 'warning');

// Find all github repos in our Awesome PHP list
$awesomeListContent = getAwesomeListContents();
$githubRepos = findUrlsInMarkdown('github.com', $awesomeListContent);

// Github action annotations
$annotations = [];

foreach($githubRepos as $repoUrl) {
    // get the username/repo from the URL
    preg_match('/github.com\/([^\/]+\/[^\/]+)$/', $repoUrl, $matches);
    if (!isset($matches[1]) || empty($matches[1])) {
        echo "Could not parse repo URL: $repoUrl\n";
        continue;
    }

    $repoData = fetchFromGithub('/repos/' . $matches[1], [], APHP_REQUEST_THROTTLE);

    $lastPush = strtotime($repoData['pushed_at']);
    $isArchived = $repoData['archived'];

    // determine the lines where this repo is mentioned in the markdown file
    $lines = [];
    foreach(explode("\n", $awesomeListContent) as $lineNumber => $line) {
        if (strpos($line, $repoUrl) !== false) {
            $lines[] = $lineNumber + 1;
        }
    }

    if ($lastPush < time() - APHP_LAST_PUSH_MAX_AGE || $isArchived) {
        foreach($lines as $line) {
            $annotations[] = sprintf(
                "::%s file=%s,line=%d,col=0::Abandoned repository, last push at '%s' (archived: %s)", 
                APHP_GH_ANNOTATION_LEVEL,
                APHP_NAME_MD, 
                $line,
                date('Y-m-d', $lastPush),
                $isArchived ? 'yes' : 'no'
            );
        }
        
        printlnRed(" - {$repoData['full_name']} last pushed at " . date('Y-m-d', $lastPush) . " (archived: " . ($isArchived ? 'yes' : 'no') . ")");
    } else {
        printlnGreen(" - {$repoData['full_name']} ok.");
    }
}

// print the annotations for GitHub Actions
foreach($annotations as $annotation) {
    echo $annotation . "\n";
}
?>
