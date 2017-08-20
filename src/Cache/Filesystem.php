<?php

namespace duncan3dc\Sql;

class Filesystem
{
    private $path;


    public function __construct(string $path)
    {
        $this->path = $path;

        # Ensure a cache directory exists for this query
        if (!is_dir($path)) {

            /**
             * We can't use the recursive feature of mkdir(), as its permissions handling is affected by umask().
             * So we create each directory individually and set the permissions using chmod().
             */
            $directories = explode("/", $path);

            $path = "/";
            foreach ($directories as $directory) {
                $path .= "/{$directory}";
                if (!is_dir($path)) {
                    mkdir($path);
                    chmod($path, 0777);
                }
            }
        }
    }


    public function getPath(): string
    {
        return $this->path;
    }


    public function has(string $file): bool
    {
        return file_exists("{$this->path}/{$file}");
    }


    public function date(string $file): int
    {
        return filectime("{$this->path}/{$file}");
    }


    /**
     * Serialize a data structure to a file.
     *
     * @param string $file The name of the file to write
     * @param array|stdClass $data The data to decode
     *
     * @return void
     */
    public function put(string $file, $data)
    {
        $string = json_encode($data);

        $this->checkLastError();

        $path = "{$this->path}/{$file}";

        # Ensure the directory exists
        $directory = pathinfo($path, \PATHINFO_DIRNAME);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (file_put_contents($path, $string) === false) {
            throw new \Exception("Failed to write the file: {$path}");
        }
    }


    /**
     * Unserialize a data structure from a file.
     *
     * @param string $file The path of the file to read
     *
     * @return \stdClass
     */
    public function get(string $file): \stdClass
    {
        $path = "{$this->path}/{$file}";

        if (!is_file($path)) {
            throw new \Exception("File does not exist: {$path}");
        }

        $string = file_get_contents($path);

        if ($string === false) {
            throw new \Exception("Failed to read the file: {$path}");
        }

        if (!$string) {
            return [];
        }

        $data = json_decode($string);

        $this->checkLastError();

        return $data;
    }


    /**
     * Check if the last json operation returned an error and convert it to an exception.
     *
     * @return void
     */
    private function checkLastError()
    {
        $error = json_last_error();

        if ($error === \JSON_ERROR_NONE) {
            return;
        }

        throw new \Exception("JSON Error: " . json_last_error_msg(), $error);
    }
}
