<?php
class Validation {
    private $errors = [];
    private $data = [];

    public function __construct($data) {
        $this->data = $data;
    }

    public function required($fields) {
        foreach ((array)$fields as $field) {
            if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
                $this->errors[$field][] = "{$field} is required";
            }
        }
        return $this;
    }

    public function email($field) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Invalid email address";
        }
        return $this;
    }

    public function phone($field) {
        if (isset($this->data[$field])) {
            $phone = preg_replace('/[^0-9]/', '', $this->data[$field]);
            if (!preg_match('/^9[78]\d{8}$/', $phone)) {
                $this->errors[$field][] = "Invalid phone number";
            }
        }
        return $this;
    }

    public function min($field, $length) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field][] = "Minimum {$length} characters required";
        }
        return $this;
    }

    public function max($field, $length) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field][] = "Maximum {$length} characters allowed";
        }
        return $this;
    }

    public function numeric($field) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = "Must be a number";
        }
        return $this;
    }

    public function positive($field) {
        if (isset($this->data[$field]) && $this->data[$field] <= 0) {
            $this->errors[$field][] = "Must be greater than zero";
        }
        return $this;
    }

    public function matches($field, $otherField) {
        if (isset($this->data[$field]) && isset($this->data[$otherField])) {
            if ($this->data[$field] !== $this->data[$otherField]) {
                $this->errors[$field][] = "Does not match {$otherField}";
            }
        }
        return $this;
    }

    public function pattern($field, $pattern, $message = "Invalid format") {
        if (isset($this->data[$field]) && !preg_match($pattern, $this->data[$field])) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function custom($field, $callback, $message = "Invalid value") {
        if (isset($this->data[$field])) {
            $result = $callback($this->data[$field]);
            if (!$result) {
                $this->errors[$field][] = $message;
            }
        }
        return $this;
    }

    public function isValid() {
        return empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError($field) {
        return isset($this->errors[$field][0]) ? $this->errors[$field][0] : null;
    }

    public function getAllMessages() {
        $messages = [];
        foreach ($this->errors as $field => $errors) {
            foreach ($errors as $error) {
                $messages[] = $error;
            }
        }
        return $messages;
    }
}