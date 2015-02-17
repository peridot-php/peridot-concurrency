<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Model;

use Peridot\Core\Suite as CoreSuite;

/**
 * Suite models the core Peridot suite, but allows the title of the test
 * to be set.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Model
 */
class Suite extends CoreSuite
{
    /**
     * @var string
     */
    private $title = '';

    /**
     * @param string $description
     */
    public function __construct($description)
    {
        parent::__construct($description, function () {
            // noop
        });
    }

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
