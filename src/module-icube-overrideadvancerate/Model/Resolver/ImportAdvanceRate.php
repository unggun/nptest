<?php

declare(strict_types=1);

namespace Icube\OverrideAdvancerate\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Icube\OverrideAdvancerate\Model\Import\ImportUploadedFile;
/**
 * Import advance rate resolver
 */
class ImportAdvanceRate implements ResolverInterface
{

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var ImportUploadedFile
     */
    private $importUploadedFile;


    public function __construct(
        Filesystem $fileSystem,
        ImportUploadedFile $importUploadedFile
    ) {
        $this->fileSystem = $fileSystem;
        $this->importUploadedFile = $importUploadedFile;
    }
    
    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['file']['file_name']) || empty($args['file']['file_name'])) {   
            throw new GraphQlInputException(__("file_name is required"));
        }

        if (!isset($args['file']['binary']) || empty($args['file']['binary'])) {   
            throw new GraphQlInputException(__("binary is required"));
        }

        try {
            if (isset($args['file']['binary']) && !empty($args['file']['binary'])) {   
                $name = explode(".",$args['file']['file_name']);
                $fileExtension = end($name);

                $fileName = $name[0].'_'.date("YmdHis").'.'.$fileExtension;                
                $uploadData = $this->decodeBase64($args['file']['binary']);
                $path = 'rateimportupload/';
                $directoryCode = DirectoryList::PUB;
                // #upload file
                $this->saveFiles($uploadData, $fileExtension, $fileName, $path, $directoryCode);
                // #import file
                $this->importUploadedFile->executeImport($fileName, $path, $directoryCode);
			}

            $status = 'Data has been successfull imported';
		} catch (\Exception $e) {
			throw new GraphQlInputException(__($e->getMessage()));
		}

        return ['status' => $status];
	}
	
    
    /**
     * Save file from binary data
     * 
     * @param string $data
     * @param string $fileExtension
     * @param string $fileName
     * @param string $path
     * @param string $directoryCode
     * @throws GraphQlInputException
     */
    protected function saveFiles($data, $fileExtension, $fileName, $path = 'rateimportupload/', $directoryCode = DirectoryList::PUB)
    {
        try {
            $this->getAllowedExtension($fileExtension);

            $directoryUpload = $this->fileSystem->getDirectoryRead($directoryCode)->getAbsolutePath($path);
            
            $directory = $this->fileSystem->getDirectoryWrite($directoryCode);
            $directory->create($directoryUpload);

            $fullPath = $directoryUpload . $fileName;
            switch ($fileExtension) {
                case 'zip':
                    // Todo: create write file zip
                    break;
                default:
                    $directory->writeFile($fullPath, $data);
                    break;
            }
        } catch (\Exception $e) {
            throw new GraphQlInputException(__('Failed to upload file'));
        }
    }

    /**
     * Decode binary base64
     * 
     * @param string $binary
     * @throws GraphQlInputException
     */
    protected function decodeBase64($binary)
    {
        if (false === (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $binary)) {
            throw new GraphQlInputException(__('Invalid format for binary data'));
        }
        return base64_decode($binary);
    }

    /**
     * Get allowed extension
     * 
     * @param string $extension
     * @throws GraphQlInputException
     */
    protected function getAllowedExtension($extension)
    {
        if ($extension == "csv") {
            return true;
        }
        throw new GraphQlInputException(__('Extension "%1" is not allowed for this request.', $extension));
    }
}
