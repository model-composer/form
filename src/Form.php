<?php namespace Model\Form;

use Model\ProvidersFinder\Providers;

class Form
{
	/** @var Field[] */
	private array $dataset = [];
	public array $options = [];
	private array $types;

	/**
	 * Form constructor.
	 *
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		$this->options = array_merge([
			'table' => null,
		], $options);
	}

	public function __clone()
	{
		foreach ($this->dataset as $f)
			$f->setForm($this);
	}

	/**
	 * Adds a new field to the dataset
	 *
	 * @param string|Field $datum
	 * @param array|string $options
	 * @return Field
	 */
	public function add(string|Field $datum, array|string $options = []): Field
	{
		if (is_string($datum)) {
			if (isset($this->dataset[$datum])) { // Already existing
				$name = $datum;
				$datum = $this->dataset[$datum];
			} else {
				$name = $datum;
				$datum = null;
			}
		} else {
			$name = $datum->options['name'];
		}

		if (!is_array($options))
			$options = ['type' => $options];

		if ($datum === null) {
			$datum = $this->buildField($name, $options);
		} else {
			$options = array_merge($datum->options, $options);
			$options['form'] = $this;
			$new_datum = $this->buildField($name, $options);
			$new_datum->setValue($datum->getValue());
			$datum = $new_datum;
		}

		$this->dataset[$name] = $datum;

		return $datum;
	}

	private function buildField(string $name, array $options): Field
	{
		$options = array_merge([
			'form' => $this,
			'type' => null,
			'nullable' => null,
			'default' => false,
			'multilang' => null,
			'depending_on' => null,
		], $options);

		$db = class_exists('\\Model\\Db\\Db') ? \Model\Db\Db::getConnection() : null;
		$mlTables = [];

		if (class_exists('\\Model\\Multilang\\Ml')) {
			if ($db)
				$mlTables = \Model\Multilang\Ml::getTables($db);

			if ($options['multilang'] === null) {
				if ($db and isset($mlTables[$this->options['table']]) and in_array($name, $mlTables[$this->options['table']]['fields']))
					$options['multilang'] = true;
				else
					$options['multilang'] = false;
			}

			if ($options['multilang'] and $this->options['table'] and !isset($mlTables[$this->options['table']]))
				$options['multilang'] = false;
		} else {
			if ($options['multilang'])
				$options['multilang'] = false;
		}

		if ($this->options['table'] and $options['multilang'])
			$table = $this->options['table'] . $mlTables[$this->options['table']]['table_suffix'];
		else
			$table = $this->options['table'];

		$tableModel = ($db and $table) ? $db->getTable($table) : null;
		$targetType = 'text';
		if ($tableModel and isset($tableModel->columns[$name])) {
			$column = $tableModel->columns[$name];
			if ($options['nullable'] === null)
				$options['nullable'] = $column['null'];

			switch ($column['type']) {
				case 'tinyint':
				case 'smallint':
				case 'int':
				case 'mediumint':
				case 'bigint':
				case 'float':
				case 'double':
				case 'real':
					if ($column['foreign_keys'])
						$targetType = $this->getLinkField($db, $column, $column['foreign_keys'][0], $options);
					else
						$targetType = 'number';
					break;

				case 'decimal':
					$length = explode(',', $column['length']);
					$options['attributes']['step'] = round($length[0] > 0 ? 1 / pow(10, $length[1]) : 1, $length[1]);
					$targetType = 'number';
					break;

				case 'enum':
					$targetType = 'select';

					if (!isset($options['options'])) {
						$options['options'] = [
							'' => $options['empty_option'],
						];
						foreach ($column['length'] as $v)
							$options['options'][$v] = ucwords($v);
					}
					break;

				case 'date':
					$targetType = 'date';
					break;

				case 'time':
					$targetType = 'time';
					break;

				case 'datetime':
					$targetType = 'datetime';
					break;

				case 'longtext':
				case 'mediumtext':
				case 'smalltext':
				case 'text':
				case 'tinytext':
					$targetType = 'textarea';
					break;

				case 'varchar':
				case 'char':
					if ($name === 'password')
						$targetType = 'password';
					break;

				case 'point':
					$targetType = 'point';
					break;
			}


			if (in_array($column['type'], ['varchar', 'char']) and $column['length'] !== false)
				$options['maxlength'] = $column['length'];

			if ($options['default'] === false)
				$options['default'] = $column['default'];
		}

		if (!$options['type'])
			$options['type'] = $targetType;

		$types = $this->getTypes();
		if (!isset($types[$options['type']]))
			throw new \Exception('Field type "' . $options['type'] . '" does not exist');

		return new $types[$options['type']]($name, $options);
	}

	private function getLinkField(\Model\Db\DbConnection $db, array $column, array $fk, array $options): string
	{
		foreach ($this->getTypes() as $type => $class) {
			if (is_subclass_of($class, LinkField::class) and $class::matchColumn($db, $column, $fk, $options))
				return $type;
		}

		return 'select';
	}

	private function getTypes(): array
	{
		if (!isset($this->types)) {
			$this->types = [];
			$providers = Providers::find('FormProvider');
			foreach ($providers as $provider)
				$this->types = array_merge($this->types, $provider['provider']::getFields());
		}

		return $this->types;
	}
}
