<?php

namespace RahulHaque\Filepond\Interfaces;

interface FilePondInterface
{
	public function scopeOwned($query);

	public function creator();

	public function delete();

	public function forceDelete();
}
