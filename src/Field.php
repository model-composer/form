<?php namespace Model\Form;

class Field
{
	public array $options = [];
	protected ?Form $form;
	protected mixed $value;

	/**
	 * Field constructor.
	 *
	 * @param string $name
	 * @param array $options
	 */
	public function __construct(public string $name, array $options = [])
	{
		$this->options = array_merge([
			'type' => null,
			'form' => null,
			'nullable' => null,
			'default' => null,
			'value' => false,
			'multilang' => false,
			'depending_on' => null,
			'maxlength' => false,

			'attributes' => [],
			'label_attributes' => [],
			'required' => false,
			'label' => null,
		], $options);

		$this->form = $this->options['form'];

		if ($this->options['multilang'] and !class_exists('\\Model\\Multilang\\Ml'))
			$this->options['multilang'] = false;

		if ($this->options['value'] === false)
			$this->options['value'] = $this->options['default'];
		$this->setValue($this->options['value']);
	}

	/**
	 * @param Form $form
	 */
	public function setForm(Form $form): void
	{
		$this->options['form'] = $form;
		$this->form = $form;
	}

	/**
	 * Sets the current value
	 *
	 * @param mixed $v
	 * @param string|null $lang
	 */
	public function setValue(mixed $v, ?string $lang = null): void
	{
		if ($this->options['multilang']) {
			if (!isset($this->value))
				$this->value = [];

			if ($lang) {
				$this->value[$lang] = $v;
			} else {
				if (is_array($v)) {
					$this->value = $v;
				} else {
					if (!is_array($this->value))
						$this->value = [];
					$this->value[\Model\Multilang\Ml::getLang()] = $v;
				}
			}
		} else {
			$this->value = $v;
		}
	}

	/**
	 * Returns the current value
	 * For multilang fields, if no $lang is passed, the current one will be used; if false is passed, an array with all languages will be returned
	 *
	 * @param string|false|null $lang
	 * @return mixed
	 */
	public function getValue(string|null|false $lang = null): mixed
	{
		if ($this->options['type'] === 'password')
			return null;

		if ($this->options['multilang']) {
			if ($lang === false) {
				return $this->value;
			} else {
				if ($lang === null)
					$lang = \Model\Multilang\Ml::getLang();

				return $this->value[$lang] ?? null;
			}
		} else {
			return $this->value;
		}
	}
}
