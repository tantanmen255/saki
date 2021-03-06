<?php

namespace SingletonTest;

class SingletonMockClass1 extends \Saki\Util\Singleton {
    function __toString() {
        return 'SingletonMockClass1';
    }
}

class SingletonMockClass2 extends SingletonMockClass1 {
    function __toString() {
        return 'SingletonMockClass2';
    }
}

class SingletonMockClass3 extends SingletonMockClass2 {
    function __toString() {
        return 'SingletonMockClass3';
    }
}

class SingletonTest extends \SakiTestCase {
    function testInheritance() {
        $s1 = SingletonMockClass1::create();
        $s2 = SingletonMockClass2::create();
        $this->assertInstanceOf('SingletonTest\SingletonMockClass1', $s1);
        $this->assertInstanceOf('SingletonTest\SingletonMockClass2', $s2);
        $this->assertNotEquals($s1, $s2);
    }

    function testIdentity() {
        $s1 = SingletonMockClass1::create();
        $s1Another = SingletonMockClass1::create();
        $this->assertSame($s1, $s1Another);
    }

    function testUnsafeInheritance() {
        $s3 = SingletonMockClass3::create();
        $this->assertInstanceOf('SingletonTest\SingletonMockClass3', $s3);
    }
}