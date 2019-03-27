<?php
namespace Angeldm\CopyThemeOverride\Console\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Module\Dir\Reader;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateThemeCommand extends Command
{
    public $area;
    public $package;
    public $name;
    public $theme_path;

    protected $directoryList;
    protected $writeFactory;
    protected $helper;
    protected $reader;

    public function __construct(
        DirectoryList $directoryList,
        WriteFactory $writeFactory,
        Reader $reader
        ) {
        $this->directoryList = $directoryList;
        $this->writeFactory = $writeFactory;
        $this->reader = $reader;
        $this->helper = new CommandHelper($this);

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('dev:theme:generate')
            ->addOption(
                'area',
                '-a',
                InputOption::VALUE_REQUIRED,
                'Which colors do you like?',
                ['adminhtml', 'frontend']
            )
            ->addArgument('package', InputArgument::REQUIRED, 'Package like "Vendor"')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the theme')
            ->setDescription('Creates a new theme');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootPath  =  $this->directoryList->getRoot();
        $path = $this->directoryList->getPath('app');
        $area = $input->getOption('area');
        $package = $input->getArgument('package');
        $name = $input->getArgument('name');
        $path ="$path/degign/$area/$package/$name";
        $output->writeln($path);
        $this->area = $area;
        $this->package = $package;
        $this->name = $name;
        $this->theme_path = $path;
        $luma = "$rootPath/vendor/magento/theme-frontend-luma";
        $this->recursiveRemoveDirectory($path);
        $this->recursiveCopy($luma, $path);
        $this->writeRegistrationFile();
        $this->writeComposerFile();
        $this->writeThemeFile();
    }

    public function writeComposerFile()
    {
        $moduleDirectory = $this->reader->getModuleDir('', 'Angeldm_CopyThemeOverride');
        $template_file_path =  "$moduleDirectory/templates/json/composer.tpl";
        $output_file_path =  "$this->theme_path/composer.json";
        $template_file_contents = file_get_contents($template_file_path);
        $data = [
              'package_name_lc'=>strtolower($this->package),
              'module_name_lc'=>strtolower($this->name)
          ];
        $output_file_contents = $this->replacePlaceHolders($template_file_contents, $data);
        $this->writeCodeFile($output_file_path, $output_file_contents);
    }

    public function writeThemeFile()
    {
        $moduleDirectory = $this->reader->getModuleDir('', 'Angeldm_CopyThemeOverride');
        $template_file_path = "$moduleDirectory/templates/xml/theme.tpl";
        $output_file_path = "$this->theme_path/theme.xml";
        $template_file_contents = file_get_contents($template_file_path);
        $data = [
            'name'=>$this->name
        ];
        $output_file_contents = $this->replacePlaceHolders($template_file_contents, $data);
        $this->writeCodeFile($output_file_path, $output_file_contents);
    }

    public function writeRegistrationFile()
    {
        $moduleDirectory = $this->reader->getModuleDir('', 'Angeldm_CopyThemeOverride');
        $template_file_path = "$moduleDirectory/templates/php/registration.tpl";
        $output_file_path = "$this->theme_path/registration.php";
        $template_file_contents = file_get_contents($template_file_path);
        $data = [
            'area'=>$this->area,
            'package'=>$this->package,
            'name'=>$this->name
        ];
        $output_file_contents = $this->replacePlaceHolders($template_file_contents, $data);
        $this->writeCodeFile($output_file_path, $output_file_contents);
    }

    public function writeCodeFile($file_path, $content)
    {
        if (!file_exists(dirname($file_path))) {
            mkdir(dirname($file_path), 0755, true);
        }

        $output_file = fopen($file_path, "w") or die("Unable to open file: " . $file_path);
        fwrite($output_file, $content);
        fclose($output_file);

        echo "\e[32m File created: " . $file_path . "\e[0m \n";
    }

    public function replacePlaceHolders($template_file_contents, $data)
    {
        foreach ($data as $find=>$replace) {
            $template_file_contents = str_replace('{{' . $find . '}}', $replace, $template_file_contents);
        }

        return $template_file_contents;
    }

    public function recursiveRemoveDirectory($directory, $empty = false)
    {
        // if the path has a slash at the end we remove it here
        if (substr($directory, -1) === '/') {
            $directory = substr($directory, 0, -1);
        }
        // if the path is not valid or is not a directory ...
        // ... if the path is not readable
        if (!is_dir($directory) || !is_readable($directory)) {
            return false;
        }
        // we open the directory
        $handle = opendir($directory);
        if (!$handle) {
            throw new RuntimeException(sprintf('Directory <%s> error', $directory));
        }
        $skip = ['.', '..'];
        // and scan through the items inside
        while (false !== ($file = readdir($handle))) {
            // if the filepointer is not the current directory
            // or the parent directory
            if (in_array($file, $skip)) {
                continue;
            }
            // we build the new path to delete
            $path = $directory . '/' . $file;
            // if the new path is a directory
            // don't recursively delete symlinks - just remove the actual link
            // this is helpful for extensions sym-linked from vendor directory
            // previous behaviour would wipe out the files in the vendor directory
            if (!is_link($path) && is_dir($path)) {
                // we call this function with the new path
                $this->recursiveRemoveDirectory($path);
            // if the new path is a file
            } else {
                // we remove the file
                unlink($path);
            }
        }
        closedir($handle);
        // if the option not empty
        if (!$empty) {
            return rmdir($directory);
        }
        // return success
        return true;
    }

    public function recursiveCopy($src, $dst, $blacklist = [])
    {
        if (!is_dir($dst)) {
            @mkdir($dst, 0777, true);
        }
        if (!is_dir($dst)) {
            throw new RuntimeException(sprintf('Destination directory <%s> error', $dst));
        }
        $handle = opendir($src);
        if (!$handle) {
            throw new RuntimeException(sprintf('Source directory <%s> error', $src));
        }
        $skip = array_merge(['.', '..'], $blacklist);
        $stack = [];
        while (false !== ($file = readdir($handle))) {
            if (in_array($file, $skip)) {
                continue;
            }
            if (is_dir($src . '/' . $file)) {
                $stack[] = $file;
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
        closedir($handle);
        foreach ($stack as $file) {
            $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file, $blacklist);
        }
    }
}
