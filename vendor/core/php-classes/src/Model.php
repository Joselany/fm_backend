<?php 

namespace Core;

class Model {
    protected $values = [];
    protected $fields = [];
    protected $fieldTypes = [];

    public function getValue($field)
    {
        return isset($this->values[$field]) ? $this->values[$field] : null;
    }
    
    public function setValue($fieldname, $value)
    {
        if (in_array($fieldname, $this->fields))
        {
            if (isset($this->fieldTypes[$fieldname])) {
                $type = $this->fieldTypes[$fieldname];
                switch ($type) {
                    case 'int':
                        $value = is_numeric($value) ? (int) $value : 0;
                        break;
                    case 'float':
                        $value = is_numeric($value) ? (float) $value : 0.0;
                        break;
                    case 'string':
                        $value = (string) $value;
                        break;
                }
            }
            $this->values[$fieldname] = $value;
        }
    }

    public function getValues()
    {
        return $this->values;
    }

    public function setValues($data)
    {
        foreach($data as $key => $value)
        {
            $this->setValue($key, $value);
        }
    }
}

?>