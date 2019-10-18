<?php
declare(strict_types = 1);

namespace App\Command;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ClearHttpCacheCommand extends Command
{
    /**
     * @var
     */
    protected $output;

    /**
     * @var
     */
    protected $directoryPath;

    /**
     * @var
     */
    protected $expirationHours;

    /**
     * @array
     */
    protected  $deleteCount = [
        'response' => 0,
        'metadata' => 0
    ];

    /**
     * DateTime
     */
    protected $now;

    /**
     * @var
     */
    protected $filesystem;


    public function __construct()
    {
        parent::__construct();

        $this->now =  new \DateTime("now");
        $this->filesystem = new Filesystem();

    }

    protected function configure()
    {
        $this
            ->setName('cache-clear')
            ->setDescription('Clears the Symfony Http Cache')
            ->setHelp('This command allows you to delete the expired files from the http_cache directory')
            ->addArgument('path', InputArgument::REQUIRED, 'The path to your HttpCache cache folder, e.g. var/cache/prod/http_cache')
            ->addOption('hours', null, InputOption::VALUE_OPTIONAL, 'How many hours you want to keep the cache files?', 4)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->directoryPath = $input->getArgument('path');
        $this->expirationHours = $input->getOption('hours');

        // Cast the output to a property for easy outputting of information.
        $this->output = $output;

        if ($this->directoryPath === false) {
            $output->writeln( '<error>HTTP Cache folder not found. Exiting command</error>');
            exit;
        }

        if ($this->expirationHours === false) {
            $output->writeln( '<error>Number of hours not specified. Exiting command </error>');
            exit;
        }

        $output->writeln('Clearing the HTTP Cache stored at: ' . $this->directoryPath) ;

        $output->writeln('==========') ;

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
            if (strpos($fileInfo->getPathname(), '/md/') !== false) {

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
                $linkedResponseFilePath = $this->getLinkedResponseFileFromMetadata($metadata);

                if (!is_file($linkedResponseFilePath)) {
                    $output->writeln('No response file found.');
                } else {

                    $responseFilesArray[] = $linkedResponseFilePath;

                    // Delete the linked response file if it exists
                    $output->writeln('Deleting corresponding response file: ' . $linkedResponseFilePath);
                    $this->filesystem->remove($linkedResponseFilePath);

                    // Increase the number of files deleted
                    $this->deleteCount['response']++;
                }

                // Delete the metadata file
                $output->writeln('Deleting expired metadata file: ' . $fileInfo->getPathname());
                $this->filesystem->remove($fileInfo->getPathname());

                // Increase the number of files deleted
                $this->deleteCount['metadata']++;
            }


            // Find the response files
            if (strpos($fileInfo->getPathname(), '/en/') !== false) {

                $output->writeln('Processing the response directory') ;


                if(!in_array($fileInfo->getPathname(),$responseFilesArray)) {

                    // Delete the response files that do not have a corresponding metadata file
                    $output->writeln('Deleting the response file with no corresponding metadata file: ' . $fileInfo->getPathname());
                    $this->filesystem->remove($fileInfo->getPathname());

                    // Increase the number of files deleted
                    $this->deleteCount['response']++;
                }
            }
        }

        $output->writeln($this->deleteCount['response'] . '<info> response files deleted successfully!<info>') ;
        $output->writeln($this->deleteCount['metadata'] . '<info> metadata files deleted successfully!<info>') ;

        $output->writeln('<info>Clearing the HTTP Cache complete!<info>') ;

    }


    /**
     * Method that builds the response file path from the metadata content
     *
     * @param $metadata
     * @return string
     */
    protected function getLinkedResponseFileFromMetadata($metadata)
    {
        // Getting the reference to the HTTP response file
        $content = $metadata[0][1]['x-content-digest'];

        if (!$content) {
            return;
        }

        // Get first 6 characters and build the response file path from the content reference
        $responseDirectoryArray = str_split(substr($content[0], 0, 6), 2);
        $responseFileName = substr($content[0], 6);

        $responseFilePath = $this->directoryPath . '/'. implode('/', $responseDirectoryArray).'/'. $responseFileName;

        return $responseFilePath;

    }

}
