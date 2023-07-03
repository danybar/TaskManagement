<?php declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Utils\Finder;
use Nette\Utils\FileSystem;
use App\Model\Service\UserService;
use App\Model\Service\ProjectService;
use App\Model\Service\TaskService;
use Nette\Application\UI\Form;

class FilePresenter extends Nette\Application\UI\Presenter
{
    private TaskService $taskService;

    public function __construct(private UserService $userService, private ProjectService $projectService, TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function getUserNameById(int $userId): ?string
    {
        return $this->userService->getUserNameById($userId);
    }

    public function getAvatarById(int $userId): ?string
    {
        return $this->userService->getAvatarById($userId);
    }

    public function createDirectory(int $userId): void
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . "/user_files/$userId";
        if (!is_dir($path)) {
            FileSystem::createDir($path);
        }
    }

    public function getFiles(int $userId): array
{
    $path = $_SERVER['DOCUMENT_ROOT'] . "/user_files/$userId";

    if (!is_dir($path)) {
        throw new \Exception('Složka neexistuje!');
    }

    $basePath = $this->getHttpRequest()->getUrl()->getBaseUrl();

    $files = [];
    foreach (Finder::findFiles('*')->from($path) as $file) {
        $sizeInBytes = $file->getSize();
        $sizeInMB = round($sizeInBytes / (1024 * 1024), 2);
        $created = date('Y-m-d H:i:s', $file->getMTime());
        $filePath = ltrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', $file->getPathname()), '/');
        $url = $basePath . '/' . $filePath;
        $fileExtension = pathinfo($file->getFilename(), PATHINFO_EXTENSION); 
        $files[] = [
            'name' => $file->getFilename(),
            'size' => $sizeInMB,
            'created' => $created,
            'url' => $url,
            'extension' => $fileExtension, 
        ];
    }

    usort($files, function ($a, $b) {
        return strtotime($b['created']) - strtotime($a['created']);
    });

    return $files;
}

    public function actionDeleteFile(string $fileName): void
{
    $userId = $this->getUser()->getId();

    $path = $_SERVER['DOCUMENT_ROOT'] . "/user_files/$userId/$fileName";

    if (!file_exists($path)) {
        throw new \Exception('Soubor neexistuje!');
    }

    unlink($path);
    $this->flashMessage('Soubor byl úspěšně smazán.');
    $this->redirect('File:files');
}


    public function renderFiles($id)
    {
        $this->createDirectory($this->getUser()->getId());
        $this->template->accountName = $this->getUserNameById($this->getUser()->getId());
        $this->template->files = $this->getFiles($this->getUser()->getId());
        $this->template->showCopyLinkModal = true;
        $this->template->avatar = $this->getAvatarById($this->getUser()->getId());
    }

    public function renderEdit($id)
    {
        $this->createDirectory($this->getUser()->getId());
        $this->template->accountName = $this->getUserNameById($this->getUser()->getId());
        $this->template->avatar = $this->getAvatarById($this->getUser()->getId());
    }

    protected function createComponentFileForm(): Form
    {
        $form = new Form;

        $form->addUpload('file', 'Soubor')
            ->setRequired('Vyberte soubor.');

        $form->addSubmit('save', 'Uložit změny');
        
        $form->onSuccess[] = [$this, 'fileFormSucceeded'];

        return $form;
    }

    public function fileFormSucceeded(Form $form, \stdClass $values): void
    {
        $file = $values->file;

        if ($file->isOk()) {
            $userId = $this->getUser()->getId();
            $basePath = $_SERVER['DOCUMENT_ROOT'] . "/user_files/$userId";
            $filename = $file->getName();
            $destination = $basePath . "/" . $filename;

        
            if (file_exists($destination)) {
                $this->flashMessage("Soubor $filename již existuje.", 'danger');
                $this->redirect('this');
            }

        
            $maxFileSize = 25 * 1024 * 1024; 
            if ($file->getSize() > $maxFileSize) {
                $this->flashMessage("Soubor $filename překračuje maximální povolenou velikost (25 MB).", 'danger');
                $this->redirect('this');
            }

            try {
                FileSystem::createDir($basePath);
                $file->move($destination);
                $this->flashMessage("Soubor $filename byl úspěšně nahrán.", 'success');
            } catch (\Exception $e) {
                $this->flashMessage("Nahrávání souboru se nezdařilo.", 'danger');
            }
        } else {
            $this->flashMessage("Soubor nebyl úspěšně nahrán", 'danger');
        }

        $this->redirect('this');
    }



}
