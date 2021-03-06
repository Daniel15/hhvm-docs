<?hh // strict

namespace HHVM\UserDocumentation;

use FredEmmott\DefinitionFinder\ScannedBase;
use FredEmmott\DefinitionFinder\ScannedClass;
use FredEmmott\DefinitionFinder\ScannedFunctionAbstract;
use FredEmmott\DefinitionFinder\HasScannedGenerics;
use FredEmmott\DefinitionFinder\HasScannedVisibility;

abstract final class ScannedDefinitionFilters {
  public static function IsHHSpecific(ScannedBase $def): bool {
    $is_hh_specific =
      strpos($def->getName(), 'HH\\') === 0
      || strpos($def->getName(), '__SystemLib\\') === 0
      || $def->getAttributes()->containsKey('__HipHopSpecific')
      || strpos($def->getName(), 'fb_') === 0
      || strpos($def->getName(), 'hphp_') === 0;

    if ($is_hh_specific) {
      return true;
    }

    if ($def instanceof HasScannedGenerics && $def->getGenericTypes()) {
      return true;
    }

    if ($def instanceof ScannedClass) {
      foreach ($def->getMethods() as $method) {
        if (self::IsHHSpecific($method)) {
          return true;
        }
      }
    }

    if (!$def instanceof ScannedFunctionAbstract) {
      return false;
    }

    if ($def->getReturnType()?->getTypeName() === 'Awaitable') {
      return true;
    }

    if (
      $def->getReturnType()?->getTypeName() === 'ExternalThreadEventWaitHandle'
    ) {
      return true;
    }

    return false;
  }

  public static function ShouldNotDocument(ScannedBase $def): bool {
    return (
      (strpos($def->getName(), "__SystemLib\\") === 0)
      || strpos($def->getName(), 'WaitHandle')
      || self::IsBlacklisted($def)
      || self::IsUndefinedFunction($def)
    );
  }

  private static function IsUndefinedFunction(ScannedBase $def): bool {
    if (!$def instanceof ScannedFunctionAbstract) {
      return false;
    }
    $name = $def->getName();
    if (function_exists($name) || function_exists("HH\\".$name)) {
      return false;
    }
    Log::w("\nUndefined function: ".$def->getName());
    return true;
  }

  private static function IsBlacklisted(ScannedBase $def): bool {
    // In an ideal world, everything in HH\ should be documented,
    // nothing else should be. Things currently there that are internal
    // should be moved to the __SystemLib\ namespace.
    //
    // That's long-term cleanup unlikely to be finished soon and we don't
    // want to block the doc site rewrite on it, so, for now, we have
    // this blacklist.
    //
    // As meta points:
    //  - The xxxAccess interfaces for collections are covered by things like
    //    ConstSet, ConstMap, etc. The others are implementation details.

    // Do not include "HH\" in the blacklist - we automatically strip it.

    $blacklist = [
      /////////////
      // Classes //
      /////////////

      'AppendIterator',
      'ArrayIterator',
      'BuiltinEnum',
      'CachingIterator',
      'CallbackFilterIterator',
      'Client\TypecheckResult',
      'EmptyIterator',
      'FilterIterator',
      'Generator',
      'InfiniteIterator',
      'IntlIterator',
      'IteratorIterator',
      'LimitIterator',
      'MapIterator',
      'MultipleIterator',
      'MySSLContextProvider',
      'NoRewindIterator',
      'ParentIterator',
      'RecursiveArrayIterator',
      'RecursiveCachingIterator',
      'RecursiveCallbackFilterIterator',
      'RecursiveFilterIterator',
      'RecursiveIteratorIterator',
      'RecursiveRegexIterator',
      'RecursiveTreeIterator',
      'ReflectionFunctionAbstract',
      'RegexIterator',
      'ResourceBundle',
      'SessionHandler',
      'SetIterator',
      'SplDoublyLinkedList',
      'SplFixedArray',
      'SplHeap',
      'SplMaxHeap',
      'SplMinHeap',
      'SplObjectStorage',
      'SplPriorityQueue',
      'SplQueue',
      'SplStack',
      'VectorIterator',

      ////////////////
      // Interfaces //
      ////////////////

      'ArrayAccess',
      'ConstIndexAccess',
      'ConstMapAccess',
      'ConstSetAccess',
      'IndexAccess',
      'IteratorAggregate',
      'MapAccess',
      'OuterIterator',
      'RecursiveIterator',
      'SQLListFormatter',
      'SQLScalarFormatter',
      'SeekableIterator',
      'SetAccess',

      ///////////////
      // Functions //
      ///////////////

      'apache_get_config',
      'array_column',
      'array_fill',
      'array_filter',
      'array_key_exists',
      'array_keys',
      'array_values',
      'arsort',
      'asort',
      'call_use_func_array',
      'krsort',
      'ksort',
      'lz4_hccompress',
      'lz4compress',
      'lz4uncompress',
      'lzhccompress',
      'mysql_fetch_result',
      'nzcompress',
      'nzuncompress',
      'rsort',
      'snuncompress',
      'sort',
      'uasort',
      'uksort',
      'usort',
    ];
    $keyed = array_flip($blacklist);

    $name = $def->getName();
    if (strpos($name, "HH\\") === 0) {
      $name = substr($name, 3);
    }

    return array_key_exists($name, $keyed);
  }
}
