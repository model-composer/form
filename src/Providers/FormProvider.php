<?php namespace Model\Form\Providers;

use Model\Form\AbstractFormProvider;
use Model\Form\Field;
use Model\Form\Password;
use Model\Form\Select;

class FormProvider extends AbstractFormProvider
{
	public static function getFields(): array
	{
		return [
			'text' => Field::class,
			'number' => Field::class,
			'select' => Select::class,
			'password' => Password::class,
			'date' => Field::class,
			'time' => Field::class,
			'datetime' => Field::class,
			'textarea' => Field::class,
			'point' => Field::class,
		];
	}
}
