<?php
namespace Xearts\Provider\Upload;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Upload\File;
use Upload\Storage\FileSystem;
use Upload\Validation\Extension;
use Upload\Validation\Mimetype;
use Upload\Validation\Size;

class UploadServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {


        $pimple['uploads'] = function ($pimple) {
            $uploads = new Container();
            foreach ($pimple['uploads.options'] as $name => $options) {
                $uploads[$name] = function ($uploads) use ($options, $pimple) {
                    /** @var File $file */
                    $file = $pimple['upload.file.factory']($options);

                    if ($validation = $pimple['upload.validation.mime_type.factory']($options)) {
                        $file->addValidations($validation);
                    }
                    if ($validation = $pimple['upload.validation.size.factory']($options)) {
                        $file->addValidations($validation);
                    }
                    if ($validation = $pimple['upload.validation.extension.factory']($options)) {
                        $file->addValidations($validation);
                    }

                    return $file;
                };
            }

            return $uploads;
        };

        $this->registerFileFactory($pimple);
        $this->registerStorageFactory($pimple);
        $this->registerValidationFactory($pimple);

    }

    public function registerFileFactory(Container $pimple)
    {
        $pimple['upload.file.factory'] = $pimple->protect(function ($options) use ($pimple) {
            if (empty($options['paramname'])) {
                throw new \RuntimeException('paramname is not defined.');
            }
            return new File($options['paramname'], $pimple['upload.storage.factory']($options));
        });
    }

    public function registerStorageFactory(Container $pimple)
    {
        $pimple['upload.storage.factory'] = $pimple->protect(function ($options) use ($pimple) {
            if (empty($options['directory'])) {
                throw new \RuntimeException('directory is not defined.');
            }
            if (empty($pimple['upload_dir_path'])) {
                throw new \RuntimeException('upload_dir_path is not defined.');
            }
            $pathToDirectory = $pimple['upload_dir_path'].$options['directory'];

            if (!empty($options['date_directory'])) {
                $pathToDirectory .= '/' . date('Ymd');
            }

            if (!file_exists($pathToDirectory) || !is_dir($pathToDirectory)) {
                mkdir($pathToDirectory, 0755, true);
            }

            return new FileSystem($pathToDirectory);
        });
    }


    public function registerValidationFactory(Container $pimple)
    {
        $pimple['upload.validation.mime_type.factory'] = $pimple->protect(function ($options) use ($pimple) {
            if (isset($options['mimetypes'])) {
                return new Mimetype($options['mimetypes']);
            }
            return null;
        });
        $pimple['upload.validation.size.factory'] = $pimple->protect(function ($options) use ($pimple) {
            if (isset($options['size'])) {
                return new Size($options['size']);
            }
            return null;
        });
        $pimple['upload.validation.extension.factory'] = $pimple->protect(function ($options) use ($pimple) {
            if (isset($options['extension'])) {
                return new Extension($options['extension']);
            }
            return null;
        });
    }
}
