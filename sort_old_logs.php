<?php
$logsDirectory = 'logs/';
$yesterday = new DateTime('yesterday');
$dirIterator = new DirectoryIterator($logsDirectory);

foreach ($dirIterator as $fileinfo) {
    if ($fileinfo->isFile()) {
        $filename = $fileinfo->getFilename();
        // Expected filename format: {prefix}_YYYY-MM-DD-HH-MM-SS.log
        $pattern = '/_(\d{4})-(\d{2})-(\d{2})-/'; // Regex to extract date from filename
        if (preg_match($pattern, $filename, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];

            $fileDate = new DateTime("$year-$month-$day");
            if ($fileDate < $yesterday) {
                // Determine the directory path based on the date
                $monthName = strtolower(date('M', mktime(0, 0, 0, $month, 10))); // Convert month number to short month name
                $newDirectory = $logsDirectory . "$year/$monthName $year/$monthName $day $year/";

                // Create directory if it does not exist
                if (!is_dir($newDirectory)) {
                    mkdir($newDirectory, 0755, true);
                }

                // Move the file
                rename($logsDirectory . $filename, $newDirectory . $filename);
            }
        }
    }
}
echo "Logs sorted successfully.";
?>
