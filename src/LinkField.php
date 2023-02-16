<?php namespace Model\Form;

abstract class LinkField extends Field
{
	public static function matchColumn(\Model\Db\DbConnection $db, array $column, array $fk, array $options): bool
	{
		return false;
	}
}
