<?php

// Delete all files in /tmp of each host

// "find /local/ledduy/ -name '*flag' | xargs rm"
if ($argc != 4)
{
    printf("Usage %s <HostStart> <HostEnd> <Del Pattern>\n", $argv[0]);
    printf("Example: %s 101 114 'rm -rf /local/ledduy/*' \n", $argv[0]);
    printf("Example: %s 501 514 \"find /local/ledduy/ -name '*flag*' | xargs rm -rf\" \n", $argv[0]);
    printf("Example: %s 501 514 \"du -hs /local/ledduy/\" \n", $argv[0]);
    exit();
}

$nHostStart = intval($argv[1]);
$nHostEnd = intval($argv[2]);
$szDelWhat = $argv[3];
for ($j = $nHostStart; $j < $nHostEnd; $j ++)
{
    $szHostName = sprintf("bc%d", $j);
    // $szCmdLine = sprintf("ssh %s 'rm -rf /tmp/TRE*'", $szHostName);
    $szCmdLine = sprintf("ssh %s '%s' &", $szHostName, $szDelWhat);
    printf("Command [%s]\n", $szCmdLine);
    system($szCmdLine);
}

?>