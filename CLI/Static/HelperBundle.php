<?php

namespace DesignInk\WordPress\Framework\CLI\Static;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A helper class for combining the question helper and IO interfaces for terminal questions.
 */
final class HelperBundle
{
    /**
     * Promote all constructor parameters to class properties.
     * 
     * @param QuestionHelper $helper The question helper for posing terminal prompts.
     * @param InputInterface $input The terminal input interface.
     * @param OutputInterface $output The terminal output interface.
     */
    public function __construct(
        public QuestionHelper $helper,
        public InputInterface $input,
        public OutputInterface $output
    ) { }
}
