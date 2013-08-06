<?php
/*
Software License Agreement (BSD License)

Copyright (c) 2008 Scott MacVicar
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:
1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. The name of the author may not be used to endorse or promote products
   derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class PlistParser
{
	private $stack;
	private $currentKey;
	private $root;
	private $data;

	public function parse($stream)
	{
		$this->reset();

		$parser = xml_parser_create('UTF-8');
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, 'handleBeginElement', 'handleEndElement');
		xml_set_character_data_handler($parser, 'handleData');
		xml_parse($parser, file_get_contents($stream));
		xml_parser_free($parser);

		return $this->root;
	}

	private function reset()
	{
		$this->stack = new PListStack();
		$this->currentKey = NULL;
		$this->root = NULL;
	}

	private function handleBeginElement($parser, $name, $attributes)
	{
		$this->data = array();
		$handler = 'begin_' . $name;
		if (method_exists($this, $handler)) {
			call_user_func(array($this, $handler), $attributes);
		}
	}

	private function handleEndElement($parser, $name)
	{
		$handler = 'end_' . $name;
		if (method_exists($this, $handler)) {
			call_user_func(array($this, $handler));
		}
	}

	private function handleData($parser, $data)
	{
		$this->data[] = $data;
	}

	private function addObject($value)
	{
		if ($this->currentKey != NULL) {
			$this->stack->top()->offsetSet($this->currentKey, $value);
			$this->currentKey = NULL;
		} else if ($this->stack->isEmpty()) {
			$this->root = $value;
		} else {
			$this->stack->top()->append($value);
		}
	}

	private function getData()
	{
		$data = implode('', $this->data);
		$this->data = array();
		return $data;
	}

	private function begin_dict($attrs)
	{
		$a = new PlistDict();
		$this->addObject($a);
		$this->stack->push($a);
	}

	private function end_dict()
	{
		$this->stack->pop();
	}

	private function end_key()
	{
		$this->currentKey = $this->getData();
	}

	private function begin_array($attrs)
	{
		$a = new PlistArray();
		$this->addObject($a);
		$this->stack->push($a);
	}

	private function end_array()
	{
		$this->stack->pop();
	}

	private function end_true()
	{
		$this->addObject(true);
	}

	private function end_false()
	{
		$this->addObject(false);
	}

	private function end_integer()
	{
		$this->addObject(intval($this->getData()));
	}

	private function end_real()
	{
		$this->addObject(floatval($this->getData()));
	}

	private function end_string()
	{
		$this->addObject($this->getData());
	}

	private function end_data()
	{
		$this->addObject(base64_decode($this->getData()));
	}

	private function end_date()
	{
		$this->addObject(new Date($this->getData()));
	}
}

class PListStack
{
	private $stack   = array();

	public function pop()
	{
		return array_pop($this->stack);
	}

	public function push($data)
	{
		array_push($this->stack, $data);
		return true;
	}

	public function top()
	{
		return end($this->stack);
	}

	public function count()
	{
		return count($this->stack);
	}

	public function isEmpty()
	{
		return ($this->count() == 0);
	}
}

class PlistDict extends ArrayObject
{
}

class PlistArray extends ArrayObject
{
}

?>