<?php

namespace MarcinTest\ACF\Blocks\Content;

use NanoSoup\Nemesis\ACF\BaseFields;
use NanoSoup\Nemesis\ACF\Blocks\Block;
use NanoSoup\Nemesis\ACF\Blocks\BlockInterface;
use Timber\Timber;


/**
 * Class ContentWithImage
 * @package MarcinTest\ACF\Blocks
 */
class HeroSocial extends Block implements BlockInterface
{
    /**
     * ContentWithImage constructor.
     */
    public function __construct()
    {
        parent::__construct();
        add_action('acf/init', [$this, 'registerBlock']);
        add_action('acf/init', [$this, 'registerFieldGroup']);
    }

    /**
     * Register the block with ACF
     */
    public function registerBlock(): void
    {
        $this->setBlockName('hero_social')
            ->setBlockTitle('Hero Social')
            ->setBlockCallback([self::class, 'renderBlock'])
            ->setBlockIcon('align-right')
            ->setCat('content')
            ->setPostTypes(['page', 'post', 'support-articles'])
            ->saveBlock();
    }

    /**
     * Render the block using HTML/Twig
     *
     * @param $block
     */
    public static function renderBlock($block): void
    {
        $vars['block'] = $block;
        $vars['fields'] = get_fields();

        Timber::render('/classes/ACF/Blocks/Content/views/hero-social.twig', $vars);

    }

    /**
     * Register the field group with ACF
     */
    public function registerFieldGroup(): void
    {
        $fields = new BaseFields();
        $prefix = 'hero_social';

        acf_add_local_field_group([
            'key' => 'group_block_' . $prefix,
            'title' => 'Hero with social icons',
            'fields' => [
                $fields->text($prefix . __FUNCTION__, 'Title'),
                $fields->image($prefix . __FUNCTION__, 'Background Image'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/hero-social',
                    ]
                ]
            ],
        ]);
    }
}