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
        $this->registerStorageProto($pimple);
        $this->registerValidationProto($pimple);

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

    public function registerStorageProto(Container $pimple)
    {
        $pimple['upload.storage.factory'] = $pimple->protect(function ($options) use ($pimple) {
            if (empty($options['directory'])) {
                throw new \RuntimeException('directory is not defined.');
            }
            $pathToDirectory = $options['directory'];
            if (!empty($options['date_directory'])) {
                $pathToDirectory .= '/' . date('Ymd');
            }

            return new FileSystem($pathToDirectory);
        });
    }


    public function registerValidationProto(Container $pimple)
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
