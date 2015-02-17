<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Model;

use Peridot\Core\Test as CoreTest;

/**
 * Test models the core Peridot Test, but allows the title to be set.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Model
 */
class Test extends CoreTest
{
    /**
     * @var string
     */
    private $title = '';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the title used by this test.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
}
