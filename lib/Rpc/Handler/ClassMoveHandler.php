<?php

namespace Phpactor\Rpc\Handler;

use Phpactor\Rpc\Handler;
use Phpactor\Rpc\ActionRequest;
use Phpactor\Application\ClassMove;
use Phpactor\Rpc\Editor\OpenFileAction;
use Phpactor\Rpc\Editor\InputCallbackAction;
use Phpactor\Rpc\Editor\Input\TextInput;
use Phpactor\Application\Logger\NullLogger;
use Phpactor\Application\ClassMover;
use Phpactor\Rpc\Editor\StackAction;
use Phpactor\Rpc\Editor\EchoAction;
use Phpactor\Rpc\Editor\Input\ChoiceInput;

class ClassMoveHandler implements Handler
{
    /**
     * @var ClassMover
     */
    private $classMove;

    /**
     * @var string
     */
    private $defaultFilesystem;

    public function __construct(ClassMover $classMove, string $defaultFilesystem)
    {
        $this->classMove = $classMove;
        $this->defaultFilesystem = $defaultFilesystem;
    }

    public function name(): string
    {
        return 'move_class';
    }

    public function defaultParameters(): array
    {
        return [
            'source_path' => null,
            'dest_path' => null,
            'confirmed' => false,
        ];
    }

    public function handle(array $arguments)
    {
        if (false === $arguments['confirmed']) {
            return EchoAction::fromMessage('Cancelled');
        }

        if (null === $arguments['dest_path']) {

            // get destination path
            return InputCallbackAction::fromCallbackAndInputs(
                ActionRequest::fromNameAndParameters(
                    $this->name(),
                    [
                        'source_path' => $arguments['source_path'],
                        'dest_path' => null,
                    ]
                ),
                [
                    TextInput::fromNameLabelAndDefault('dest_path', 'Move to: ', $arguments['source_path']),
                ]
            );
        }

        if (null === $arguments['confirmed']) {
            return StackAction::fromActions([
                EchoAction::fromMessage(
                    'WARNING: This command will move the class and update ALL references in the git tree.' .
                    '         It is not guaranteed to succeed. COMMIT YOUR WORK FIRST!'
                ),
                InputCallbackAction::fromCallbackAndInputs(
                    ActionRequest::fromNameAndParameters(
                        $this->name(),
                        [
                            'source_path' => $arguments['source_path'],
                            'dest_path' => $arguments['dest_path'],
                        ]
                    ),
                    [
                        ChoiceInput::fromNameLabelAndDefault(
                            'confirmed',
                            'Are you sure? :',
                            [
                                'Yes' => true, 'No' => false,
                            ],
                            'Yes'
                        ),
                    ]
                )
            ]);
        }

        $this->classMove->move(
            new NullLogger(),
            $this->defaultFilesystem,
            $arguments['source_path'],
            $arguments['dest_path']
        );

        return StackAction::fromActions([
            CloseFileAction::fromPath($arguments['source_path']),
            OpenFileAction::fromPath($arguments['dest_path'])
        ]);
    }
}
