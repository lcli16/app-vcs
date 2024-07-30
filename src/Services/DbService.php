<?php

namespace AppVcs\Services;

interface DbService {
 
	public function query($sql);
	public function error();
}