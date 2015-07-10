<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\View\Compiler\Processors;

use Spiral\Components\View\Compiler\Compiler;
use Spiral\Components\View\Compiler\ProcessorInterface;
use Spiral\Components\View\Compiler\Processors\Templater\BehaviourInterface;
use Spiral\Components\View\Compiler\Processors\Templater\Behaviours\BlockBehaviour;
use Spiral\Components\View\Compiler\Processors\Templater\Behaviours\ExtendBehaviour;
use Spiral\Components\View\Compiler\Processors\Templater\Behaviours\IncludeBehaviour;
use Spiral\Components\View\Compiler\Processors\Templater\Contexts\ImportContext;
use Spiral\Components\View\Compiler\Processors\Templater\Node;
use Spiral\Components\View\Compiler\Processors\Templater\NodeSupervisor;
use Spiral\Components\View\Compiler\Processors\Templater\TemplaterException;
use Spiral\Components\View\ViewManager;
use Spiral\Support\Html\Tokenizer;

class TemplateProcessor implements ProcessorInterface, NodeSupervisor
{
    /**
     * Primary token types supported by spiral.
     */
    const TYPE_BLOCK   = 'block';
    const TYPE_EXTENDS = 'extends';
    const TYPE_USE     = 'use';
    const TYPE_INCLUDE = 'include';

    /**
     * View manager.
     *
     * @var ViewManager
     */
    protected $viewManager = null;

    /**
     * Compiler.
     *
     * @var Compiler
     */
    protected $compiler = null;

    /**
     * Templater rendering options and names.
     *
     * @var array
     */
    protected $options = [
        'separator' => '.',
        'prefixes'  => [
            self::TYPE_BLOCK   => ['block:', 'section:'],
            self::TYPE_EXTENDS => ['extends:']
        ],
        'use'       => ['use', 'import']
    ];

    //RENAME
    public $uses = [];

    /**
     * New processors instance with options specified in view config.
     *
     * @param ViewManager $viewManager
     * @param Compiler    $compiler SpiralCompiler instance.
     * @param array       $options
     */
    public function __construct(ViewManager $viewManager, Compiler $compiler, array $options)
    {
        $this->viewManager = $viewManager;
        $this->compiler = $compiler;
    }

    /**
     * Performs view code pre-processing. LayeredCompiler will provide view source into processors,
     * processors can perform any source manipulations using this code expect final rendering.
     *
     * @param string $source View source (code).
     * @return string
     */
    public function process($source)
    {
        $root = new Node($this, 'root', $source);

        echo "\n\n\n\n\n\n\n\n";

        if (strpos('exception', $this->compiler->getView()) !== false)
        {
            //TODO: remove later
            return $source;
        }

        return $root->compile();
    }

    public function getBehaviour(array $token, array $content, Node $node)
    {
        if (!empty($type = $this->tokenType($token, $name, $node)))
        {
            switch ($type)
            {
                case self::TYPE_BLOCK:
                    return new BlockBehaviour($name);
                    break;
                case self::TYPE_EXTENDS:
                    $node = $this->getNode($name, $name);

                    //TODO: Optimize
                    $this->uses = $node->getSupervisor()->uses;

                    return new ExtendBehaviour(
                        $node,
                        $token[Tokenizer::TOKEN_ATTRIBUTES]
                    );

                    break;
                case self::TYPE_INCLUDE:
                    return new IncludeBehaviour(
                        $this,
                        $name,
                        $content,
                        $token[Tokenizer::TOKEN_ATTRIBUTES]
                    );

                    break;
                case self::TYPE_USE:
                    array_unshift($this->uses, [
                        $token[Tokenizer::TOKEN_ATTRIBUTES]['path']
                        => $token[Tokenizer::TOKEN_ATTRIBUTES]['as']
                    ]);

                    break;
            }

            return BehaviourInterface::SKIP_TOKEN;
        }

        return BehaviourInterface::SIMPLE_TAG;
    }

    protected function tokenType($token, &$name, Node $context)
    {
        $name = $token[Tokenizer::TOKEN_NAME];
        foreach ($this->options['prefixes'] as $type => $prefixes)
        {
            foreach ($prefixes as $prefix)
            {
                if (strpos($name, $prefix) === 0)
                {
                    $name = substr($name, strlen($prefix));

                    return $type;
                }
            }
        }

        if (in_array($name, $this->options['use']))
        {
            return self::TYPE_USE;
        }

        if ($name == 'include')
        {
            return self::TYPE_INCLUDE;
        }

        //We may have some problems here

        //We have to check imported blocks here

        return null;
    }

    public function getNode($name, $view)
    {
        $compiler = $this->compiler->getCopy('default', $view);

        //We have to pre-compile view
        $source = $compiler->getSource();

        foreach ($compiler->getProcessors() as $processor)
        {
            if ($processor instanceof self)
            {
                //The rest will be handled by TemplateProcessor
                break;
            }

            $source = $processor->process($source);
        }

        if (empty($processor))
        {
            throw new TemplaterException("Invalid processors chain.");
        }

        return new Node($processor, $name, $source);
    }
}