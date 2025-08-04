<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Smalot\PdfParser\Parser;

class BulkUploadPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onFormProcessed' => [
                ['bulkUpload', 0]
            ]
        ];
    }

    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    public function bulkUpload(Event $event): void
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        $action = $event['action'];
      if($action == 'bulkUpload'){

        $form = $event['form'];
    
        $uploadedFiles = $form->getData()['my_files'];
        foreach ($uploadedFiles as $filePath => $fileInfo) {
                   $parser = new \Smalot\PdfParser\Parser();
          $pdf = $parser->parseFile($fileInfo['path']);
          $metadata = $pdf->getDetails();

          $shabbos = strtotime("next Saturday", strtotime($metadata['CreationDate']));
          $date = Zmanim::jewishCalendar(Carbon::createFromDate(date('Y', $shabbos), date('m', $shabbos), date('d', $shabbos)));
          $parsha = HebrewDateFormatter::create()->setHebrewFormat(true)->formatParsha($date);

          $postName = str_replace("-", "", substr($metadata['CreationDate'],0,10));

          $folder = __DIR__ . '/../../pages/04.limuday/'.$postName;
          $file = $folder . '/item.md';

          if (!file_exists($folder)) {
            mkdir($folder);
          }

          copy($fileInfo['path'], $folder."/".$fileInfo['name']);
          $text = $pdf->getText();

          $index = 11;
          $lines = explode("\n", $text);
          foreach ($lines as $index => $line) {
            if (strpos($line, 'Halachic') !== false) break; //halacha
          }
          $this->grav['log']->debug('index: '.date('d', $shabbos)); 
          $title = (!empty($lines[$index + 1])) ? $lines[$index + 1] : 'title'; //11,2,6
          $this->grav['log']->debug('title: '.$title);
          $content = null;
          if (file_exists($file)) $content = file_get_contents($file);

          $path = $postName.'/'.$fileInfo['name'];
          $data = 'data="'.$path.'"';
          if ($content==null) $content = "---\ntitle: $title\n---\n";
          file_put_contents($file, $content."<object $data type='application/pdf' width='100%' height='500px'><p>$parsha</p></object>");
        }
      }

        // Enable the main events we are interested in
        $this->enable([
        ]);
    }
}
