<?php

namespace WebAtrio\Bundle\SchemaExportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\ORM\Mapping\ClassMetadata;
use WebAtrio\Bundle\SchemaExportBundle\ErrorConstants;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use WebAtrio\Bundle\SchemaExportBundle\Field;
use WebAtrio\Bundle\SchemaExportBundle\Utils;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class JsonCommand extends ContainerAwareCommand {

    /**
     * @var ClassMetadata[]
     */
    private $allMetaData;
    private $destinationFolder;
    private $output;

    public function __construct(array $allMetaData) {
        if ($allMetaData == []) {
            throw new \Exception(
            'No Doctrine Entities on your system.'
            );
        }
        $this->allMetaData = $allMetaData;
        parent::__construct();
    }

    protected function configure() {
        $this
                ->setName('web-atrio:schema:generate:json')
                ->setDescription('Convert doctrine entities into json')
                ->addArgument('destination_folder', InputArgument::REQUIRED, 'In which folder to generate the .json files ?');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->destinationFolder = $input->getArgument('destination_folder');
        $this->output = $output;
        if (!file_exists($this->destinationFolder)) {
            throw new FileNotFoundException(ErrorConstants::CONVERT_ERROR_FOLDER_NOT_FOUND);
        } elseif (!is_writable($this->destinationFolder)) {
            throw new IOException(ErrorConstants::CONVERT_ERROR_FOLDER_NOT_WRITABLE);
        }
        $output->writeln('<info>Generating Json....</info>');
        foreach ($this->allMetaData as $singleMeta) {
            $this->output->writeln($singleMeta->getName());
            $className = Utils::getClassName($singleMeta->getName());

            $file = $this->destinationFolder . '/' . $className . '.json';
            $fields = $singleMeta->getFieldNames();
            $list = array();

            // Generate all fields
            foreach ($fields as $field) {
                $currentField = new Field($field, $singleMeta->getFieldMapping($field)['type']);
                if (array_key_exists("nullable", $singleMeta->getFieldMapping($field))) {
                    $currentField->setNullable($singleMeta->getFieldMapping($field)['nullable']);
                }
                if (array_key_exists("length", $singleMeta->getFieldMapping($field))) {
                    $currentField->setLength($singleMeta->getFieldMapping($field)['length']);
                }
                $list[] = $currentField;
            }

            // Generate all association
            $associations = $singleMeta->getAssociationMappings();
            foreach ($associations as $association) {
                $field = $association["fieldName"];
                $fieldType = Utils::getClassName($association["targetEntity"]);
                $nullable = (array_key_exists("joinColumns", $association) && $association["joinColumns"] && array_key_exists("nullable", $association["joinColumns"][0]) && $association["joinColumns"][0]["nullable"]);
                $list[] = new Field($field, $fieldType, $nullable);
            }

            $encoders = array(new JsonEncoder());
            $normalizers = array(new ObjectNormalizer());

            $serializer = new Serializer($normalizers, $encoders);

            $content = $serializer->serialize($list, 'json');
            file_put_contents($file, $content);
        }
    }

}
