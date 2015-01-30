<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Model;

use Peridot\Core\Test as CoreTest;

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
