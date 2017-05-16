<?php

namespace WebAtrio\Bundle\SchemaExportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use WebAtrio\Bundle\SchemaExportBundle\ErrorConstants;
use WebAtrio\Bundle\SchemaExportBundle\Field;
use WebAtrio\Bundle\SchemaExportBundle\Utils;

class TypeScriptCommand extends ContainerAwareCommand {

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
                ->setName('web-atrio:schema:generate:ts')
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
        $output->writeln('<info>Generating TypeScript....</info>');
        foreach ($this->allMetaData as $singleMeta) {
            $this->output->writeln($singleMeta->getName());
            $className = Utils::getClassName($singleMeta->getName());
            $fields = $singleMeta->getFieldNames();
            $file = $this->destinationFolder . '/' . $className . '.ts';
            $content = "class $className {\n\r";
            $declaration = "";
            $constructor_declaration = "\tconstructor(";
            $constructor_content = ") {\n";
            $assessor = "";
            $first = true;
            $filedList = array();

            // Generate all fields
            foreach ($fields as $field) {
                $fieldType = $this->DoctrineToTypescriptTypeConverter($singleMeta->getFieldMapping($field)['type']);
                $nullable = false;
                if (array_key_exists("nullable", $singleMeta->getFieldMapping($field))) {
                    $nullable = $singleMeta->getFieldMapping($field)['nullable'];
                }
                $filedList[] = new Field($field, $fieldType, $nullable);
                if (!$nullable) {
                    $declaration .= "\tprivate _$field: $fieldType;\n\r";
                    if (!$first) {
                        $constructor_declaration .= ", ";
                    }
                    $constructor_declaration .= "_$field: $fieldType";
                    $constructor_content .= "\t\tthis._$field = $field;\n";
                    $first = false;
                } else {
                    $declaration .= "\tprivate _$field: $fieldType = null;\n\r";
                }

                $assessor .= "\tget $field: $fieldType {\n";
                $assessor .= "\t\treturn  this._$field;\n";
                $assessor .= "\t}\n\r";
                $assessor .= "\tset $field(value: $fieldType) {\n";
                $assessor .= "\t\tthis._$field = value;\n";
                $assessor .= "\t}\n\r";
            }

            // Generate all association
            $filedList = array();
            $associations = $singleMeta->getAssociationMappings();
            foreach ($associations as $association) {
                $nullable = false;
                if (array_key_exists("joinColumns", $association) && $association["joinColumns"] && array_key_exists("nullable", $association["joinColumns"][0]) && $association["joinColumns"][0]["nullable"]) {
                    $nullable = true;
                }
                $field = $association["fieldName"];
                $fieldType = Utils::getClassName($association["targetEntity"]);
                $filedList[] = new Field($field, $fieldType, $nullable);
                if (!$nullable) {
                    $declaration .= "\tprivate _$field: $fieldType;\n\r";
                    if (!$first) {
                        $constructor_declaration .= ", ";
                    }
                    $constructor_declaration .= "_$field: $fieldType";
                    $constructor_content .= "\t\tthis._$field = $field;\n";
                    $first = false;
                } else {
                    $declaration .= "\tprivate _$field: $fieldType = null;\n\r";
                }

                $assessor .= "\tget $field: $fieldType {\n";
                $assessor .= "\t\treturn  this._$field;\n";
                $assessor .= "\t}\n\r";
                $assessor .= "\tset $field(value: $fieldType) {\n";
                $assessor .= "\t\tthis._$field = value;\n";
                $assessor .= "\t}\n\r";
            }
            $constructor_content .= "\t}\n\r";

            $content .= $declaration . $constructor_declaration . $constructor_content . $assessor . "}";

            file_put_contents($file, $content);
        }
    }

    protected function DoctrineToTypescriptTypeConverter($type) {

        switch ($type) {
            case "integer":
                return "number";
                break;
            case "smallint":
                return "number";
                break;
            case "datetime":
                return "Date";
                break;
            case "array":
                return "Array<string>";
                break;
            default:
                return $type;
        }
    }

}
