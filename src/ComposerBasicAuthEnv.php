<?php

namespace ComposerBasicAuthEnv;

use Composer\Plugin\PluginInterface;
use Composer\Composer;
use Composer\IO\IOInterface;

class ComposerBasicAuthEnv implements PluginInterface
{

    public function activate(Composer $composer, IOInterface $io)
    {
        $path = join(DIRECTORY_SEPARATOR, [getcwd(), '.env']);
        if (!file_exists($path)) {
            // if ($io && $io->isDebug()) {
                $io->writeError("<error>{$path} not found</error>");
            // }
            return;
        }
        if (!is_readable($path)) {
            // if ($io && $io->isDebug()) {
                $io->writeError("<error>.env file is not readable</error>");
            // }
            return;
        }
        $composerAuthEnv = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {

            if ($this->strStartsWith($line, "#CBA") == false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $parts = explode(":", $value, 2);
            if (count($parts) != 2) {
                continue;
            }
            $host = trim($this->strBetween($name, "[", "]"));
            $username = trim($parts[0]);
            $password = trim($parts[1]);
            if ($host && $username && $parts) {
                $composerAuthEnv['http-basic'][$host] = compact('username', 'password');
            }
        }
        $authData = array_filter($composerAuthEnv);
        if ($authData) {
            $config = $composer->getConfig();
            $io->writeError("<info>Setting basic auth from .env</info>");
            $config->merge(array('config' => $authData), 'COMPOSER_AUTH');
            $composer->setConfig($config);
            $io->loadConfiguration($config);
        }
    }
    protected function strStartsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function strAfter($subject, $search)
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    protected function strBeforeLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $pos = mb_strrpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return mb_substr($subject, 0, $pos);
    }

    protected function strBetween($subject, $from, $to)
    {
        if ($from === '' || $to === '') {
            return $subject;
        }

        return $this->strBeforeLast($this->strAfter($subject, $from), $to);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}
