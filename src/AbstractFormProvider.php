<?php namespace Model\Form;

use Model\ProvidersFinder\AbstractProvider;

abstract class AbstractFormProvider extends AbstractProvider
{
	public abstract static function getFields(): array;
}
