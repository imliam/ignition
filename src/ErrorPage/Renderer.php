<?php

namespace Spatie\Ignition\ErrorPage;

use Exception;
use Spatie\Ignition\Exceptions\ViewException;

class Renderer
{
    protected string $viewPath;

    public function __construct(string $viewPath)
    {
        $this->viewPath = $this->formatPath($viewPath);
    }

    public function render(string $viewName, array $_data)
    {

        $viewFile = "{$this->viewPath}{$viewName}.php";
$_data['report']['application_path'] = '';
        try {
            extract((array) $_data, EXTR_OVERWRITE);

            include $viewFile;
        } catch (Exception $exception) {
            $viewException = new ViewException($exception->getMessage());
            $viewException->setView($viewFile);
            $viewException->setViewData($_data);

            throw $viewException;
        }


    }

    protected function formatPath(string $path): string
    {
        return preg_replace('/(?:\/)+$/u', '', $path).'/';
    }
}