<?php

/**
 * @var string $pharFilename
 */
$pharFilename = 'raspi-setup.phar';

/**
 * @param SplFileInfo $current
 * @param string $key pathname (assuming default constructor)
 * @param RecursiveCallbackFilterIterator $iterator
 */
$pharAddFilterCallback = function ($current, $key, $iterator) {
    $keyRelative = strpos($key, __DIR__) === 0 ? ltrim(substr($key, strlen(__DIR__)), '/') : $key;
    if ($current->isDir()) {
        if ($keyRelative === '..') {
            // Yeah, go away ".."!
            return false;
        }
        if (in_array($keyRelative, ['src', '.git', '.idea'], true)) {
            // Skip the root /src/, .git and .idea dirs
            return false;
        }
        if (strpos($keyRelative, 'vendor/') === 0 && preg_match('#^(Example|Test)s?$#i', $current->getFilename())) {
            // Skip 'Example' and 'Test' dirs inside /vendor/
            return false;
        }
        if ($iterator->hasChildren()) {
            return true;
        }
    } else {
        if ($current->getPathname() === __FILE__) {
            // Skip this file...
            return false;
        }
        if (preg_match('#^(LICEN[CS]E\b|README\b|composer\b)#i', $current->getFilename())) {
            // Include licence files, readmes, etc., for the sake of legality & courtesy
            return true;
        }
        if (strcasecmp(substr($current->getFilename(), -4), '.php') !== 0) {
            // Only include *.php files
            return false;
        }
    }

    // If we've not excluded stuff above, it's probably okay to include!
    return true;
};

/**
 * @var int $pharCompression
 */
//$pharCompression = Phar::NONE;
$pharCompression = Phar::GZ;
//$pharCompression = Phar::BZ2; // Note: bz2 extension isn't default on Windows

/**
 * @var bool $removeUncompressed
 */
$removeUncompressed = true;

#####################################

$pharFullFilename = $pharFilename;
$pharCompressionName = 'none';
switch ($pharCompression) {
    case Phar::BZ2:
        if (!in_array('BZIP2', Phar::getSupportedCompression(), true)) {
            throw new RuntimeException("BZIP2 compression was requested, but your PHP installation does not support BZIP2 compression.");
        }

        $pharFullFilename .= '.bz2';
        $pharCompressionName = 'BZIP2';
        echo "- Target Phar archive filename is `$pharFilename`.\r\n";
        echo "- Target Phar archive will utilise BZIP2 compression.\r\n";
        echo "- Target Phar archive will be compressed to become `$pharFullFilename`.\r\n";
        break;
    case Phar::GZ:
        if (!in_array('GZ', Phar::getSupportedCompression(), true)) {
            throw new RuntimeException("GZ compression was requested, but your PHP installation does not support GZ compression.");
        }

        $pharFullFilename .= '.gz';
        $pharCompressionName = 'GZIP';
        echo "- Target Phar archive filename is `$pharFilename`.\r\n";
        echo "- Target Phar archive will utilise GZIP compression.\r\n";
        echo "- Target Phar archive will be compressed to become `$pharFilename`.\r\n";
        break;
    case Phar::NONE:
        $pharCompressionName = 'none';
        echo "- Target Phar archive filename is `$pharFilename`.\r\n";
        echo "- Target Phar archive will not utilise any compression.\r\n";
        break;
    default:
        throw new RuntimeException("An unrecognised compression format has been specified in \$pharCompression!!");
}

if (!Phar::canWrite()) {
    throw new RuntimeException(
        "Your PHP installation isn't configured to permit writing of Phar archives. ".
        "Ensure that you have the `phar.readonly` php.ini directive set to 'Off'."
    );
}
echo "- PHP is correctly configured to permit creation of Phar archives.\r\n";

if (file_exists($pharFilename)) {
    echo "- Target uncompressed Phar archive, `$pharFilename`, already exists.\r\n";
    echo "- Deleting `$pharFilename`.\r\n";

    if (!@unlink($pharFilename)) {
        throw new RuntimeException("Unable to delete `$pharFilename`!");
    }
}

if ($pharFilename !== $pharFullFilename && file_exists($pharFullFilename)) {
    echo "- Target compressed Phar archive, `$pharFullFilename`, already exists.\r\n";
    echo "- Deleting `$pharFullFilename`.\r\n";

    if (!@unlink($pharFullFilename)) {
        throw new RuntimeException("Unable to delete `$pharFullFilename`!");
    }
}

$phar = new Phar($pharFilename);
echo "- Initialised PHP Phar object for `$pharFilename`.\r\n";

if (!$phar->isWritable()) {
    throw new RuntimeException(
        "PHP is claiming that the destination Phar filename, `$pharFilename`, is not writable. ".
        "You should verify that the target's parent directory is writable by your user account."
    );
}

echo "- Including all relevant files from `" . __DIR__ . "` and all child directories.\r\n";
$phar->buildFromIterator(
    new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator(__DIR__),
            $pharAddFilterCallback
        )
    ),
    __DIR__
);

$fileCount = $phar->count();
if (!$fileCount) {
    throw new RuntimeException("No files were added to the Phar archive!");
}

if (!file_exists($pharFilename)) {
    throw new RuntimeException("The uncompressed Phar archive `$pharFilename` should have been created but it doesn't appear to exist!");
}
$uncompressedArchiveSize = filesize($pharFilename);
if ($uncompressedArchiveSize <= 0) {
    throw new RuntimeException("The uncompressed Phar archive `$pharFilename` should have been created but appears to be an empty file!");
}

$uncompressedSizeFormatted = number_format(round($uncompressedArchiveSize / 1024), 0, '.', ',');
echo "- Successfully included $fileCount files into the Phar archive for a total size of $uncompressedSizeFormatted kB.\r\n";

if ($pharCompression !== Phar::NONE) {
    echo "- Compressing the Phar archive using `$pharCompressionName` to `$pharFullFilename`.\r\n";
    $phar->compress($pharCompression);
    if (!file_exists($pharFullFilename)) {
        throw new RuntimeException("The compressed Phar archive `$pharFullFilename` should have been created but it doesn't appear to exist!");
    }
    $compressedArchiveSize = filesize($pharFullFilename);
    if ($compressedArchiveSize <= 0) {
        throw new RuntimeException("The compressed Phar archive `$pharFullFilename` should have been created but appears to be an empty file!");
    }
    $compressedSizeFormatted = number_format(round($compressedArchiveSize / 1024), 0, '.', ',');
    $percSaving = 100 - round(($compressedArchiveSize / $uncompressedArchiveSize) * 100, 1);
    echo "- Successfully compressed `$pharFilename` to `$pharFullFilename` with a resulting size of $compressedSizeFormatted kB - a saving of $percSaving%!\r\n";

    if ($removeUncompressed) {
        echo "- Removing uncompressed interim archive file, `$pharFilename`.\r\n";
        unset($phar);
        Phar::unlinkArchive($pharFilename);
    }
}
