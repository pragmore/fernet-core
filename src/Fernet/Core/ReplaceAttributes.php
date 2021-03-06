<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Framework;
use Stringable;
use function get_class;

class ReplaceAttributes
{
    private const REGEX_FORM_SUBMIT = '/<form.*?(@(onSubmit)=(["\'])(.*?)\3)/';
    private const REGEX_A_ONCLICK = '/<a.*?(@(onClick)=(["\'])(.*?)\3)/';
    private const REGEX_BIND_INPUT = '/<input.*?(@(bind)=(["\'])(.*?)\3)/';
    private const REGEX_BIND_TEXTAREA = '/<textarea(.*?)(@bind=(["\'])((?:\\\\3|(?:(?!\3)).)*)(\3))([^>]*)>/';
    private Routes $routes;

    public function __construct(Routes $routes)
    {
        $this->routes = $routes;
    }

    public function replace(string $content, Stringable $component): string
    {
        $class = get_class($component);
        $classWithoutNamespace = $class;
        $namespaces = Framework::config('componentNamespaces');
        foreach ($namespaces as $namespace) {
            $classWithoutNamespace = str_replace($namespace.'\\', '', $classWithoutNamespace);
        }

        $raws = [];
        $contents = [];
        foreach ([
            static::REGEX_FORM_SUBMIT => 'action="%s" method="POST"',
            static::REGEX_A_ONCLICK => 'href="%s"',
        ] as $regexp => $attr) {
            if (preg_match_all($regexp, $content, $matches)) {
                foreach ($matches[1] as $i => $key) {
                    $raws[] = $matches[1][$i];
                    $definition = $matches[4][$i];
                    $args = null;
                    if (preg_match('/(.+)\((.*)\)$/', $definition, $match)) {
                        [, $definition, $args] = $match;
                        $args = @unserialize(html_entity_decode($args), ['allowed_classes' => true]);
                    }
                    try {
                        $url = $this->routes->get($classWithoutNamespace, $definition, $args);
                    } catch (Exception) {
                        $url = false;
                    }
                    if (!$url) {
                        $url = Framework::config('urlPrefix').Helper::hyphen($classWithoutNamespace).'/'.Helper::hyphen($definition);
                        if ($args) {
                            $param = [];
                            foreach ($args as $arg) {
                                $param[] = serialize($arg);
                            }
                            $url .= '?'.htmlentities(http_build_query(['fernet-params' => $param]));
                        }
                    }
                    $contents[] = sprintf($attr, $url);
                }
            }
        }
        foreach ([
                     static::REGEX_BIND_INPUT => 'name="%s" value="%s"',
                 ] as $regexp => $attr) {
            if (preg_match_all($regexp, $content, $matches)) {
                foreach ($matches[1] as $i => $key) {
                    $raws[] = $matches[1][$i];
                    $definition = $matches[4][$i];
                    $value = $component;
                    $vars = explode('.', $definition);
                    foreach ($vars as $var) {
                        $value = $value->$var;
                    }
                    $contents[] = sprintf($attr, "fernet-bind[$definition]", $value);
                }
            }
        }
        if (preg_match_all(static::REGEX_BIND_TEXTAREA, $content, $matches)) {
            foreach ($matches[1] as $i => $key) {
                $raws[] = $matches[0][$i];
                $before = $matches[1][$i];
                $after = $matches[6][$i];
                $definition = $matches[4][$i];
                $value = $component;
                $vars = explode('.', $definition);
                foreach ($vars as $var) {
                    $value = $value->$var;
                }
                $contents[] = "<textarea$before name=\"fernet-bind[$definition]\"$after>$value";
            }
        }

        return str_replace($raws, $contents, $content);
    }
}
