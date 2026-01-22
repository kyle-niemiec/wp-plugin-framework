<?php

namespace WPPF\CLI\Static;

/**
 * An enumeration of colors which are compatible with the Symfony Console.
 */
enum ConsoleColor: string
{
	case Black = 'black';
	case Blue = 'blue';
	case BrightBlue = 'bright-blue';
	case BrightCyan = 'bright-cyan';
	case BrightGreen = 'bright-green';
	case BrightMagenta = 'bright-magenta';
	case BrightRed = 'bright-red';
	case BrightWhite = 'bright-white';
	case BrightYellow = 'bright-yellow';
	case Cyan = 'cyan';
	case Default = 'default';
	case Gray = 'gray';
	case Green = 'green';
	case Magenta = 'magenta';
	case Red = 'red';
	case White = 'white';
	case Yellow = 'yellow';
}
