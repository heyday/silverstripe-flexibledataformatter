<?php

/**
 * Class FlexibleDataFormatter
 */
abstract class FlexibleDataFormatter
{
    /**
     * @param DataObject $obj
     * @param array      $config
     * @return array|bool
     */
    public function convertDataObjectToArray(
        DataObject $obj,
        $config = array()
    ) {
        $content = array();

        $allowedFields = $obj instanceof FlexibleDataFormatterInterface
            ? $obj->getAllowedFields($config)
            : array_keys($obj->db());

        foreach ($allowedFields as $fieldName) {
            if ($obj->hasMethod($fieldName)) {
                $fieldValue = $obj->$fieldName();
            } else {
                $fieldValue = $obj->dbObject($fieldName);
                if (is_null($fieldValue)) {
                    $fieldValue = $obj->$fieldName;
                }
            }

            if ($fieldValue instanceof Object) {
                switch (get_class($fieldValue)) {
                    case 'Boolean':
                        $content[$fieldName] = (boolean)$fieldValue->getValue();
                        break;
                    case 'PrimaryKey':
                        $content[$fieldName] = $obj->$fieldName;
                        break;
                    case 'HTMLText':
                        $content[$fieldName] = $fieldValue->forTemplate();
                        break;
                    default:
                        $content[$fieldName] = $fieldValue->getValue();
                        break;
                }
            } else {
                $content[$fieldName] = $fieldValue;
            }
        }

        if ($obj instanceof FlexibleDataFormatterInterface) {

            foreach ($obj->getAllowedHasOneRelations($config) as $relName) {
                if ($obj->{$relName . 'ID'}) {
                    $content[$relName] = $this->convertDataObjectToArray($obj->$relName(), $config);
                }
            }

            foreach ($obj->getAllowedHasManyRelations($config) as $relName) {
                $items = $obj->$relName();
                if ($items instanceof SS_List && count($items) > 0) {
                    $content[$relName] = array();
                    foreach ($items as $item) {
                        $content[$relName][] = $this->convertDataObjectToArray($item, $config);
                    }
                }
            }

            foreach ($obj->getAllowedManyManyRelations($config) as $relName) {
                $items = $obj->$relName();
                if ($items instanceof SS_List && count($items) > 0) {
                    $content[$relName] = array();
                    foreach ($items as $item) {
                        $content[$relName][] = $this->convertDataObjectToArray($item, $config);
                    }
                }
            }

        }

        return $content;
    }
    /**
     * @param DataObject $obj
     * @param array      $config
     * @return bool
     */
    public function convertDataObject(
        DataObject $obj,
        $config = array()
    ) {
        $content = $this->convertDataObjectToArray($obj, $config);
        if (is_array($content)) {
            return $this->format($content);
        } else {
            return false;
        }
    }
    /**
     * @param SS_List       $set
     * @param array         $config
     * @return mixed
     */
    public function convertDataObjectSet(
        SS_List $set,
        $config = array()
    ) {
        $content = array();
        if (count($set) > 0) {
            foreach ($set as $obj) {
                $objContent = $this->convertDataObjectToArray($obj, $config);
                if (is_array($objContent)) {
                    $content[] = $objContent;
                }
            }
        }

        return $this->format($content);
    }
    /**
     * @param array $arr
     * @return mixed
     */
    abstract protected function format(array $arr);
}
