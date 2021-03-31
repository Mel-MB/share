<?php
namespace Project\Core;

use Project\Core\Database\Model;

abstract class Entity extends Model{
    //Validation related properties
    public const RULE_REQUIRED   = 'required';
    public const RULE_ALPHANUM   = 'alphanum';
    public const RULE_EMAIL      = 'email';
    public const RULE_MIN        = 'min';
    public const RULE_MAX        = 'max';
    public const RULE_YEAR       = 'year';
    public const RULE_MATCH      = 'match';
    public const RULE_UNIQUE     = 'unique';
    
    public array $rules       = [];
    protected array $errors      = [];

    /*  Implement  Manager related properties in child class
        protected static Manager $manager;
        private const TABLE_NAME = '';
        private const PRIMARY_KEY = '';
        //private const FOREIGN_KEYS = ['']; */
    abstract public function __construct();
        //self::$manager = new Manager(self::TABLE_NAME, self::PRIMARY_KEY, ?self::FOREIGN_KEYS);

    //Flexible instance construction  methods
    public function populate($data): void{
        foreach($data as $key => $value){
            //check if data key matches called class property
            if(property_exists($this,$key)){
                // affect object property with associated value
                $this->{$key} = $value ?? '';
            }
        }
    }
    protected static function newInstanceFromObject(object $object): object{
        $class = get_called_class();
        $newInstance = new $class();
        $newInstance->populate(get_object_vars($object));
        return $newInstance;
    }
    
    //Object basic behaviours
    public function show(string $value, $attribute = null): object{
        $class = get_class($this);
        if(!$attribute){
            $attribute = $class::PRIMARY_KEY;
        }
        $record = $class::$manager->selectOne([$attribute => $value]);
        return $class::newInstanceFromObject($record);
    }
    public function delete(string $valueOnPk): bool{
        $class = get_class($this);
        $attribute = $class::PRIMARY_KEY;
        
        $class::$manager->delete([$attribute => $valueOnPk]);
    }
    // Model interaction
    protected function entityToArray(array $attributes = null): array{
        if(!$attributes) $attributes = get_class($this)::$model->editableAttributes();
        $array = [];

        foreach(array($this) as $key => $value){
            if(preg_match('/^( \* )/', $key)) $key = substr($key,4);
            if(in_array($key,$attributes)){
                $array[$key] = $value;
            }
        }
        return $array;
    }
    // Form handdling
    ////// Form display
    abstract protected function labels(): array;
    public function getLabel(string $attribute): string {
        return $this->labels()[$attribute] ?? $attribute;
    }
    ////// Form validation
    public function rules(): array{
        return $this->rules;
    }
    public function validate(){
        foreach ($this->rules() as $attribute => $attr_rules){
            //Assert value of the attribute
            $value = $this->{$attribute};
            //With the specified rules
            foreach($attr_rules as $rule){
                $ruleName = $rule;
                if(is_array($ruleName)) $ruleName = $rule[0];
                
                if($ruleName === self::RULE_REQUIRED && !$value){
                    $this->addError($attribute, self::RULE_REQUIRED);
                }
                if($ruleName === self::RULE_ALPHANUM && !preg_match('/^[a-zA-Z0-9_]+$/',$value)){
                    $this->addError($attribute, self::RULE_ALPHANUM);
                }
                if($ruleName === self::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)){
                    $this->addError($attribute, self::RULE_EMAIL);
                }
                if($ruleName === self::RULE_YEAR && !preg_match('/^\d{4}$/',$value)){
                    $this->addError($attribute, self::RULE_YEAR);
                }
                if($ruleName === self::RULE_MIN && strlen($value) < $rule['min']){
                    $this->addError($attribute, self::RULE_MIN, $rule);
                }
                if($ruleName === self::RULE_MAX && strlen($value) > $rule['max']){
                    $this->addError($attribute, self::RULE_MIN, $rule);
                }
                if($ruleName === self::RULE_MATCH && $value !== $this->{$rule['match']}){
                    $rule['match'] = strtolower($this->getLabel($rule['match']));
                    $this->addError($attribute, self::RULE_MATCH, $rule);
                }
                if($ruleName === self::RULE_UNIQUE){
                    $manager = get_called_class()::manager();
                    $uniqueAttr = $rule['attribute'] ?? $attribute;
                    
                    $alreadyExists = $manager->selectOne([$uniqueAttr => $value]);
                    if($alreadyExists){
                        $this->addError($attribute, self::RULE_UNIQUE, ['field'=> strtolower($this->getLabel($attribute))]);
                    }
                }
            }
        }
        return empty($this->errors);
    }
    ////// User feedback
    private function errorMessages(){
        return[
            self::RULE_REQUIRED => 'Ce champs est obligatoire',
            self::RULE_ALPHANUM => 'Seuls les caractères alphanumériques sont authorisés',
            self::RULE_EMAIL => "L'adresse email doit être valide",
            self::RULE_YEAR => "Merci de spécifier une année au format YYYY",
            self::RULE_MIN => "Taille minimale requise: :min caractères",
            self::RULE_MAX => "Taille maximale acceptée: :max caractères",
            self::RULE_MATCH => "Ce champs doit être identique à votre saisie pour :match",
            self::RULE_UNIQUE => "Un compte exite déjà avec votre :field",
        ];
    }
    private function addError(string $attribute, string $ruleName,array $params=[]){
        $message = $this->errorMessages()[$ruleName] ?? '';

        foreach($params as $key => $value){
            $message = str_replace(":{$key}", $value, $message);
        }
        
        $this->errors[$attribute][] = $message;
    }
    public function hasError(string $attribute){
        return $this->errors[$attribute] ?? false;
    }
    public function getFirstError(string $attribute){
        return $this->errors[$attribute][0] ?? false;
    }
}