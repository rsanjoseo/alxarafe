<?php
/**
 * Alxarafe. Development of PHP applications in a flash!
 * Copyright (C) 2018-2020 Alxarafe <info@alxarafe.com>
 */

namespace Alxarafe\Core\Providers;

use Alxarafe\Core\Base\Provider;
use Alxarafe\Core\Singletons\DebugTool;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator as SymfonyTranslator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Lang, give support to internationalization.
 *
 * @package Alxarafe\Core\Providers
 */
class Translator extends Provider
{
    /**
     * Default language to use if language file not exists.
     */
    public const FALLBACK_LANG = 'en';

    /**
     * Base folder where languages files are stored.
     */
    public const LANG_FOLDER = DIRECTORY_SEPARATOR . 'Languages';

    /**
     * Default language to use.
     */
    public const LANG = 'es_ES';

    /**
     * Extension of language file.
     */
    public const EXT = '.yaml';

    /**
     * Format of language file.
     */
    public const FORMAT = 'yaml';

    /**
     * The Symfony translator.
     *
     * @var SymfonyTranslator
     */
    private static $translator;

    /**
     * List of used strings.
     *
     * @var array
     */
    private static $usedStrings;

    /**
     * List of strings without translation.
     *
     * @var array
     */
    private static $missingStrings;

    /**
     * Main language folder.
     *
     * @var string
     */
    private static $languageFolder;

    /**
     * Array of all language folders.
     *
     * @var array
     */
    private static $languageFolders;

    /**
     * Lang constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $config = $this->getConfig();
        self::$languageFolder = constant('BASE_FOLDER') . self::LANG_FOLDER;
        self::$translator = new SymfonyTranslator($config['language'] ?? self::FALLBACK_LANG);
        self::$translator->setFallbackLocales([self::FALLBACK_LANG]);
        self::$translator->addLoader(self::FORMAT, new YamlFileLoader());
        self::$usedStrings = [];
        self::$missingStrings = [];
        self::$languageFolders = [];
        $this->loadLangFiles();
    }

    /**
     * Load the translation files following the priorities.
     * In this case, the translator must be provided with the routes in reverse order.
     *
     * @return void
     */
    public function loadLangFiles(): void
    {
        $langFolders = $this->getLangFolders();
        if (count($langFolders) === 0 || empty($langFolders[0])) {
            die('No language folder found');
        }

        $langFiles = Finder::create()
            ->files()
            ->name('*' . self::EXT)
            ->in($langFolders)
            ->sortByName();

        foreach ($langFiles as $langFile) {
            $langCode = str_replace(self::EXT, '', $langFile->getRelativePathName());
            try {
                Yaml::parseFile($langFile->getPathName());
                self::$translator->addResource(self::FORMAT, $langFile->getPathName(), $langCode);
            } catch (ParseException $e) {
                Logger::getInstance()::exceptionHandler($e);
                FlashMessages::getInstance()::setError($e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage());
            }
        }
    }

    /**
     * Return the lang folders.
     *
     * @return array
     */
    public function getLangFolders(): array
    {
        return array_merge(
            [$this->getBaseLangFolder()],
            self::$languageFolders
        );
    }

    /**
     * Returns the base lang folder.
     *
     * @return string
     */
    public function getBaseLangFolder(): string
    {
        return self::$languageFolder;
    }

    /**
     * Return default values
     *
     * @return array
     */
    public function getDefaultValues(): array
    {
        return ['language' => self::FALLBACK_LANG];
    }

    /**
     * Add additional language folders.
     *
     * @param array $folders
     */
    public function addDirs(array $folders = [])
    {
        foreach ($folders as $key => $folder) {
            $fullFolder = $folder . self::LANG_FOLDER;
            //            FileSystemUtils::mkdir($folders[$key], 0777, true);
            if (file_exists($fullFolder) && is_dir($fullFolder)) {
                $folders[$key] = $fullFolder;
                DebugTool::getInstance()->addMessage('messages', 'Added translation folder ' . $folders[$key]);
            }
        }
        self::$languageFolders = array_merge(self::$languageFolders, $folders);
        $this->loadLangFiles();
    }

    /**
     * Sets the language code in use.
     *
     * @param string $lang
     *
     * @return void
     */
    public function setlocale(string $lang): void
    {
        self::$translator->setLocale($lang);
    }

    /**
     * Returns an array with the languages with available translations.
     *
     * @return array
     */
    public function getAvailableLanguages(): array
    {
        $languages = [];
        $dir = $this->getBaseLangFolder();
        //        FileSystemUtils::mkdir($dir, 0777, true);
        if (file_exists($dir) && is_dir($dir)) {
            $langFiles = Finder::create()
                ->files()
                ->name('*' . self::EXT)
                ->in($dir)
                ->sortByName();

            foreach ($langFiles as $langFile) {
                $langCode = str_replace(self::EXT, '', $langFile->getRelativePathName());
                $languages[$langCode] = $this->trans('language-' . $langCode);
            }
        }
        return $languages;
    }

    /**
     * Translate the text into the default language.
     *
     * @param string      $txt
     * @param array       $parameters
     * @param string|null $domain
     * @param string|null $locale
     *
     * @return string
     */
    public static function trans(string $txt, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $translated = self::$translator->trans($txt, $parameters, $domain, $locale);
        self::verifyMissing($translated, $txt, $domain);
        return $translated;
    }

    /**
     * Stores if translation is used and if is missing.
     *
     * @param string      $translated
     * @param null|string $txt
     * @param null|string $domain
     */
    private static function verifyMissing(string $translated, $txt, $domain = null)
    {
        $domain = $domain ?? 'messages';

        $txt = (string) $txt;
        $catalogue = self::$translator->getCatalogue(self::getLocale());

        while (!$catalogue->defines($txt, $domain)) {
            if ($cat = $catalogue->getFallbackCatalogue()) {
                $catalogue = $cat;
                $locale = $catalogue->getLocale();
                if ($catalogue->has($txt, $domain) && $locale !== self::getLocale()) {
                    self::$missingStrings[$txt] = $translated;
                }
            } else {
                break;
            }
        }
        if (!$catalogue->has($txt, $domain)) {
            self::$missingStrings[$txt] = $translated;
        }
    }

    /**
     * Returns the language code in use.
     *
     * @return string
     */
    public static function getLocale(): string
    {
        return self::$translator->getLocale();
    }

    /**
     * Returns the missing strings.
     *
     * @return array
     */
    public function getMissingStrings(): array
    {
        return self::$missingStrings;
    }

    /**
     * Returns the strings used.
     *
     * @return array
     */
    public function getUsedStrings(): array
    {
        return self::$usedStrings;
    }

    /**
     * Returns the original translator.
     *
     * @return SymfonyTranslator
     */
    public function getTranslator(): SymfonyTranslator
    {
        return self::$translator;
    }
}