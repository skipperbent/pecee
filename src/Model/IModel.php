<?php
namespace Pecee\Model;
interface IModel {
	public function save();
	public function update();
	public function delete();
}