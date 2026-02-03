<?php
/**
 * WordPress Plugin Framework
 *
 * Copyright (c) 2008-2026 DesignInk, LLC
 * Copyright (c) 2026 Kyle Niemiec
 *
 * This file is licensed under the GNU General Public License v3.0.
 * See the LICENSE file for details.
 *
 * @package WPPF
 */

namespace WPPF\CLI\Support;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * A QuestionHelper that avoids ConsoleSectionOutput internal buffering side effects.
 */
final class SectionQuestionHelper extends QuestionHelper
{
	/**
	 * Ask a question, proxying ConsoleSectionOutput to avoid internal addContent bookkeeping.
	 *
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 * @param Question $question The question to ask.
	 *
	 * @return mixed The user answer.
	 */
	public function ask( InputInterface $input, OutputInterface $output, Question $question ): mixed
	{
		if ( $output instanceof ConsoleSectionOutput ) {
			$output = new SectionOutputProxy( $output );
		}

		return parent::ask( $input, $output, $question );
	}
}

/**
 * Output proxy that forwards to the wrapped output without being a ConsoleSectionOutput.
 */
final class SectionOutputProxy implements OutputInterface
{
	public function __construct( private OutputInterface $inner ) { }

	public function setFormatter( \Symfony\Component\Console\Formatter\OutputFormatterInterface $formatter )
	{
		$this->inner->setFormatter( $formatter );
	}

	public function getFormatter(): \Symfony\Component\Console\Formatter\OutputFormatterInterface
	{
		return $this->inner->getFormatter();
	}

	public function setDecorated( bool $decorated )
	{
		$this->inner->setDecorated( $decorated );
	}

	public function isDecorated(): bool
	{
		return $this->inner->isDecorated();
	}

	public function setVerbosity( int $level )
	{
		$this->inner->setVerbosity( $level );
	}

	public function getVerbosity(): int
	{
		return $this->inner->getVerbosity();
	}

	public function isQuiet(): bool
	{
		return $this->inner->isQuiet();
	}

	public function isVerbose(): bool
	{
		return $this->inner->isVerbose();
	}

	public function isVeryVerbose(): bool
	{
		return $this->inner->isVeryVerbose();
	}

	public function isDebug(): bool
	{
		return $this->inner->isDebug();
	}

	public function writeln( string|iterable $messages, int $options = self::OUTPUT_NORMAL )
	{
		$this->inner->writeln( $messages, $options );
	}

	public function write( string|iterable $messages, bool $newline = false, int $options = self::OUTPUT_NORMAL )
	{
		$this->inner->write( $messages, $newline, $options );
	}
}