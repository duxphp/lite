<?php
declare(strict_types=1);

namespace Dux\Config;

use Exception;
use Symfony\Component\Yaml\Yaml as YamlParser;
use Noodlehaus\Exception\ParseException;

class Yaml extends \Noodlehaus\Parser\Yaml
{
    /**
     * @param $filename
     * @return array
     * @throws ParseException
     */
    public function parseFile($filename): array
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \Symfony\Component\Yaml\Exception\ParseException(sprintf('File "%s" does not exist or unreadable.', $filename));
        }
        $content = file_get_contents($filename);
        return $this->parseString($content);
    }

    /**
     * @param $config
     * @return array
     * @throws ParseException
     */
    public function parseString($config): array
    {
        foreach (Config::$variables as $key => $value) {
            $config = str_replace("%$key%", $value, $config);
        }

        try {
            $data = YamlParser::parse($config, YamlParser::PARSE_CONSTANT);
        } catch (Exception $exception) {
            throw new ParseException(
                [
                    'message'   => 'Error parsing YAML string',
                    'exception' => $exception,
                ]
            );
        }

        return (array)$this->parse($data);
    }
}
