<?php
declare(strict_types = 1);

namespace Studio24\HttpCacheClear;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ClearHttpCacheCommand extends Command
{
    /** @var string */
    const HTTP_CACHE_FOLDER = 'http_cache';
    const METADATA_FOLDER = 'md';
    const RESPONSE_FOLDER = 'en';

    /** @var OutputInterface */
    protected $output;

    /** @var string */
    protected $directoryPath;

    /** @var int */
    protected $expirationHours;

    /** @var string */
    protected $environment;

    /** @var array */
    protected $deleteCount = [
        'response' => 0,
        'metadata' => 0
    ];

    /** @var \DateTime */
    protected $now;

    /** @var Filesystem */
    protected $filesystem;


    public function __construct()
    {
        parent::__construct();

        $this->now =  new \DateTime("now");
        $this->filesystem = new Filesystem();

    }

    protected function configure()
    {
        $help = <<<EOD
This command allows you to delete the expired files from the Synfony HTTP Cache, when the cache is saved to the filesystem. 

Example usage: 
./bin/http-cache-clear 

By default this clears the prod cache in var/cache/prod/http_cache for any cached files over 4 hours old. 

You can change this behaviour via options. E.g. to delete prod files over 1 hour old:
./bin/http-cache-clear --expiry=1
EOD;

        $this
            ->setName('cache-clear')
            ->setDescription('Clears the Symfony HTTP Cache')
            ->setHelp($help)
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'The path to your cache folder', 'var/cache')
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Environment to clear cache for', 'prod')
            ->addOption('expiry', null, InputOption::VALUE_REQUIRED, 'How many hours you want to expire cache files?', 4)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not delete any files, just report on how many cached files are to be removed')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->directoryPath = $input->getOption('path');
        $this->environment = $input->getOption('env');
        $this->expirationHours = $input->getOption('expiry');
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $output->writeln('<info>Dry run mode</info>');
        }

        // Cast the output to a property for easy outputting of information.
        $this->output = $output;

        $this->directoryPath = realpath($this->directoryPath);
        if ($this->directoryPath === false) {
            throw new HttpCacheException(sprintf('Symfony Cache folder not found at %s', $input->getOption('path')));
        }
        $this->directoryPath .= '/' . $this->environment . '/' . self::HTTP_CACHE_FOLDER;
        if (!is_dir($this->directoryPath)) {
            $output->writeln( '<info>No http_cache folder found, exiting!</info>');
            return;
        }

        if (!is_numeric($this->expirationHours)) {
            throw new HttpCacheException('Expiration hours must be a number');
        }

        $output->writeln('Clearing the HTTP Cache stored at: ' . $this->directoryPath) ;

        $output->writeln('==================================') ;

        $directory = new RecursiveDirectoryIterator($this->directoryPath);
        $iterator = new RecursiveIteratorIterator($directory);

        $responseFilesArray = [];

        // Iterating recursively over filesystem directories
        foreach ($iterator as $fileInfo ) {

            // Skip the folder names that are "." or ".."
            if (($fileInfo->getFilename() === '.') || ($fileInfo->getFilename() === '..')) {
                continue;
            }

            if (!is_file($fileInfo->getPathname())) {
                continue;
            }

            // Find the metadata files
            if (strpos($fileInfo->getPathname(), '/' . self::METADATA_FOLDER . '/') !== false) {

                $output->writeln('Processing the metadata directory') ;

                // Read the content of the metadata file
                $metadata = unserialize(file_get_contents($fileInfo->getPathname()));

                if (!$metadata) {
                    continue;
                }

                $output->writeln('Found file stored at' . $fileInfo->getPathname());

                // Get the date of the response
                $responseDate = $metadata[0][1]['date'];
                $responseDate = new \DateTime($responseDate[0]);

                // Calculate the difference between the dates
                $interval =  $this->now->diff($responseDate);

                // Get the total number of hours from the interval
                $hours = $interval->h + ($interval->days*24);

                // Delete expired files that are over the number of hours we specified in the command
                if ($hours <= $this->expirationHours) {
                    continue;
                }

                $output->writeln('Found old cached files. Starting the deletion process...');

                $output->writeln('Searching for the corresponding response file...');

                // Find the corresponding response file
                $linkedResponseFilePath = $this->directoryPath . '/' . $this->getLinkedResponseFileFromMetadata($metadata);

                if (!is_file($linkedResponseFilePath)) {
                    $output->writeln('No response file found.');
                } else {

                    $responseFilesArray[] = $linkedResponseFilePath;

                    // Delete the linked response file if it exists
                    if (!$dryRun) {
                        $output->writeln('Deleting corresponding response file: ' . $linkedResponseFilePath);
                        $this->filesystem->remove($linkedResponseFilePath);
                    } else {
                        $output->writeln('Found response file to delete (dry-run mode): ' . $linkedResponseFilePath);
                    }

                    // Increase the number of files deleted
                    $this->deleteCount['response']++;
                }

                // Delete the metadata file
                if (!$dryRun) {
                    $output->writeln('Deleting expired metadata file: ' . $fileInfo->getPathname());
                    $this->filesystem->remove($fileInfo->getPathname());
                } else {
                    $output->writeln('Found metadata file to delete (dry-run mode): ' . $fileInfo->getPathname());
                }

                // Increase the number of files deleted
                $this->deleteCount['metadata']++;
            }


            // Find the response files
            if (strpos($fileInfo->getPathname(), '/' . self::RESPONSE_FOLDER . '/') !== false) {

                $output->writeln('Processing the response directory') ;


                if (!in_array($fileInfo->getPathname(),$responseFilesArray)) {

                    // Delete the response files that do not have a corresponding metadata file
                    if (!$dryRun) {
                        $output->writeln('Deleting the response file with no corresponding metadata file: ' . $fileInfo->getPathname());
                        $this->filesystem->remove($fileInfo->getPathname());
                    } else {
                        $output->writeln('Found response file to delete with no corresponding metadata file (dry-run): ' . $fileInfo->getPathname());
                    }

                    // Increase the number of files deleted
                    $this->deleteCount['response']++;
                }
            }
        }

        $output->writeln($this->deleteCount['response'] . '<info> response files deleted successfully!<info>') ;
        $output->writeln($this->deleteCount['metadata'] . '<info> metadata files deleted successfully!<info>') ;

        if (!$dryRun) {
            $output->writeln('<info>Clearing the HTTP Cache complete!<info>');
        } else {
            $output->writeln('<info>Finished (dry-run)<info>');
        }

        return 0;
    }


    /**
     * Method that builds the response file path from the metadata content
     *
     * @param $metadata
     * @return string
     */
    public function getLinkedResponseFileFromMetadata($metadata)
    {
        // Getting the reference to the HTTP response file
        if (!isset($metadata[0]) || !isset($metadata[0][1]) || !isset($metadata[0][1]['x-content-digest']) || !isset($metadata[0][1]['x-content-digest'][0])) {
            return null;
        }
        $content = $metadata[0][1]['x-content-digest'][0];

        // Get first 6 characters and build the response file path from the content reference
        $responseDirectoryArray = str_split(substr($content, 0, 6), 2);
        $responseFileName = substr($content, 6);

        $responseFilePath = implode('/', $responseDirectoryArray).'/'. $responseFileName;

        return $responseFilePath;

    }

}
