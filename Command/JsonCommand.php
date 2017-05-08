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

class JsonCommand extends ContainerAwareCommand {

    /**
     * @var ClassMetadata[]
     */
    private $allMetaData;

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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $destinationFolder = $input->getArgument('destination_folder');
        if (!file_exists($destinationFolder)) {
            throw new FileNotFoundException(ErrorConstants::CONVERT_ERROR_FOLDER_NOT_FOUND);
        } elseif (!is_writable($destinationFolder)) {
            throw new IOException(ErrorConstants::CONVERT_ERROR_FOLDER_NOT_WRITABLE);
        }
        $output->writeln('<info>Generating Json....</info>');
        foreach ($this->allMetaData as $singleMeta) {
            $output->writeln($singleMeta->getName());
            $metadata = $this->getContainer()->get('validator')->getMetadataFor($singleMeta->getName());
            $constraints = $metadata->getConstrainedProperties();
            if (sizeof($constraints) > 0) {
                foreach ($constraints as $constraint) {
                    $output->writeln($constraint);
                    $output->writeln(json_encode($metadata->getPropertyMetadata($constraint)));
                    $output->writeln(json_encode($metadata->getPropertyMetadata($constraint)[0]->getPropertyValue("constraints")));
                }
            }
        }
        $file = $destinationFolder . '/' . $singleMeta->getName() . '.json';
        $fields = $singleMeta->getFieldNames();
        $list = array();
        foreach ($fields as $field) {
            $obj = new \stdClass;
            $obj->name = $field;
            $obj->type = $singleMeta->getFieldMapping($field)['type'];
            if (array_key_exists("nullable", $singleMeta->getFieldMapping($field))) {
                if ($singleMeta->getFieldMapping($field)['nullable']) {
                    $obj->required = true;
                }
            }
            if (array_key_exists("length", $singleMeta->getFieldMapping($field))) {
                $obj->length = $singleMeta->getFieldMapping($field)['length'];
            }
            $list[] = $obj;
        }
        file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT));
    }

}
