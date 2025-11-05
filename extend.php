<?php

namespace DShovchko\ImagesChecker;

use Flarum\Extend;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\TemplateNormalizations\SetAttributeOnElements;

return [
    (new Extend\Frontend('forum'))
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Formatter())
        ->configure(function (Configurator $configurator) {
            // adds attributes
            $configurator->templateNormalizer->add(
                new SetAttributeOnElements('//img[not(@loading)]', 'loading', 'lazy')
            );
            $configurator->templateNormalizer->add(
                new SetAttributeOnElements('//img[not(@height)]', 'height', '{@height}')
            );
            $configurator->templateNormalizer->add(
                new SetAttributeOnElements('//img[not(@width)]', 'width', '{@width}')
            );

            if ($configurator->BBCodes->collection->exists('IMG')) {
                // re-normalize template of image tag
                $img = $configurator->BBCodes->addFromRepository('IMG');
            }
        }),
  
    (new Extend\Console())->command(Console\ImagesCheckCommand::class),
];
