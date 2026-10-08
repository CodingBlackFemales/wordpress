<?php



namespace LearnDash\Zapier {

    class AliasAutoloader
    {
        private string $includeFilePath;

        private array $autoloadAliases = array (
  'StellarWP\\Arrays\\Arr' => 
  array (
    'type' => 'class',
    'classname' => 'Arr',
    'isabstract' => false,
    'namespace' => 'StellarWP\\Arrays',
    'extends' => 'LearnDash\\Zapier\\StellarWP\\Arrays\\Arr',
    'implements' => 
    array (
    ),
  ),
  'StellarWP\\Templates\\Config' => 
  array (
    'type' => 'class',
    'classname' => 'Config',
    'isabstract' => false,
    'namespace' => 'StellarWP\\Templates',
    'extends' => 'LearnDash\\Zapier\\StellarWP\\Templates\\Config',
    'implements' => 
    array (
    ),
  ),
  'StellarWP\\Templates\\Part_Cache' => 
  array (
    'type' => 'class',
    'classname' => 'Part_Cache',
    'isabstract' => false,
    'namespace' => 'StellarWP\\Templates',
    'extends' => 'LearnDash\\Zapier\\StellarWP\\Templates\\Part_Cache',
    'implements' => 
    array (
    ),
  ),
  'StellarWP\\Templates\\Template' => 
  array (
    'type' => 'class',
    'classname' => 'Template',
    'isabstract' => false,
    'namespace' => 'StellarWP\\Templates',
    'extends' => 'LearnDash\\Zapier\\StellarWP\\Templates\\Template',
    'implements' => 
    array (
    ),
  ),
  'StellarWP\\Templates\\Templates' => 
  array (
    'type' => 'class',
    'classname' => 'Templates',
    'isabstract' => false,
    'namespace' => 'StellarWP\\Templates',
    'extends' => 'LearnDash\\Zapier\\StellarWP\\Templates\\Templates',
    'implements' => 
    array (
    ),
  ),
  'StellarWP\\Templates\\Utils\\Conditions' => 
  array (
    'type' => 'class',
    'classname' => 'Conditions',
    'isabstract' => false,
    'namespace' => 'StellarWP\\Templates\\Utils',
    'extends' => 'LearnDash\\Zapier\\StellarWP\\Templates\\Utils\\Conditions',
    'implements' => 
    array (
    ),
  ),
  'StellarWP\\Templates\\Utils\\Paths' => 
  array (
    'type' => 'class',
    'classname' => 'Paths',
    'isabstract' => false,
    'namespace' => 'StellarWP\\Templates\\Utils',
    'extends' => 'LearnDash\\Zapier\\StellarWP\\Templates\\Utils\\Paths',
    'implements' => 
    array (
    ),
  ),
  'StellarWP\\Templates\\Utils\\Strings' => 
  array (
    'type' => 'class',
    'classname' => 'Strings',
    'isabstract' => false,
    'namespace' => 'StellarWP\\Templates\\Utils',
    'extends' => 'LearnDash\\Zapier\\StellarWP\\Templates\\Utils\\Strings',
    'implements' => 
    array (
    ),
  ),
);

        public function __construct()
        {
            $this->includeFilePath = __DIR__ . '/autoload_alias.php';
        }

        public function autoload($class)
        {
            if (!isset($this->autoloadAliases[$class])) {
                return;
            }
            switch ($this->autoloadAliases[$class]['type']) {
                case 'class':
                        $this->load(
                            $this->classTemplate(
                                $this->autoloadAliases[$class]
                            )
                        );
                    break;
                case 'interface':
                    $this->load(
                        $this->interfaceTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                case 'trait':
                    $this->load(
                        $this->traitTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                default:
                    // Never.
                    break;
            }
        }

        private function load(string $includeFile)
        {
            file_put_contents($this->includeFilePath, $includeFile);
            include $this->includeFilePath;
            file_exists($this->includeFilePath) && unlink($this->includeFilePath);
        }

        private function classTemplate(array $class): string
        {
            $abstract = $class['isabstract'] ? 'abstract ' : '';
            $classname = $class['classname'];
            if (isset($class['namespace'])) {
                $namespace = "namespace {$class['namespace']};";
                $extends = '\\' . $class['extends'];
                $implements = empty($class['implements']) ? ''
                : ' implements \\' . implode(', \\', $class['implements']);
            } else {
                $namespace = '';
                $extends = $class['extends'];
                $implements = !empty($class['implements']) ? ''
                : ' implements ' . implode(', ', $class['implements']);
            }
            return <<<EOD
                <?php
                $namespace
                $abstract class $classname extends $extends $implements {}
                EOD;
        }

        private function interfaceTemplate(array $interface): string
        {
            $interfacename = $interface['interfacename'];
            $namespace = isset($interface['namespace'])
            ? "namespace {$interface['namespace']};" : '';
            $extends = isset($interface['namespace'])
            ? '\\' . implode('\\ ,', $interface['extends'])
            : implode(', ', $interface['extends']);
            return <<<EOD
                <?php
                $namespace
                interface $interfacename extends $extends {}
                EOD;
        }
        private function traitTemplate(array $trait): string
        {
            $traitname = $trait['traitname'];
            $namespace = isset($trait['namespace'])
            ? "namespace {$trait['namespace']};" : '';
            $uses = isset($trait['namespace'])
            ? '\\' . implode(';' . PHP_EOL . '    use \\', $trait['use'])
            : implode(';' . PHP_EOL . '    use ', $trait['use']);
            return <<<EOD
                <?php
                $namespace
                trait $traitname { 
                    use $uses; 
                }
                EOD;
        }
    }

    spl_autoload_register([ new AliasAutoloader(), 'autoload' ]);
}
