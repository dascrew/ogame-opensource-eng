<?php

/******************************************************************************
 *                                                                            *
 *   bbcode.lib.php, v 0.24 2007/03/06 - This is part of xBB library          *
 *   Copyright (C) 2006-2007  Dmitriy Skorobogatov  dima@pc.uz                *
 *                                                                            *
 ******************************************************************************/

class bbcode {
	/* See the documentation for descriptions of properties and methods. */
    public $tag = '';
    public $attrib = array();
    public $text = '';
    public $syntax = array();
    public $tree = array();
    public $tags = array(
        'align'   => 'bb_align',
        'b'       => 'bb_strong',
        'color'   => 'bb_color',
        'email'   => 'bb_email',
        'font'    => 'bb_font',
        'hr'      => 'bb_hr',
        'i'       => 'bb_i',
        'img'     => 'bb_img',
        'quote'   => 'bb_quote',
        's'       => 'bb_del',
        'size'    => 'bb_size',
        'sub'     => 'bb_sub',
        'sup'     => 'bb_sup',
        'u'       => 'bb_u',
        'url'     => 'bb_a'
    );
    public $children = array(
        'align','b','color','email','font','hr','i','img',
        'quote','s','size','sub','sup','u','url'
    );
    public $mnemonics = array();
    public $autolinks = true;
    public $isClose = false;
    public $lbr = 0;
    public $rbr = 0;

    function __construct($code = '') {
        $this -> do_bbcode ($code);
    }

    function do_bbcode($code = '') {
        if (is_array($code)) {
            $is_tree = false;
            foreach ($code as $key => $val) {
                if (isset($val['val'])) {
                	$this -> tree = $code;
                	$this -> syntax = $this -> get_syntax();
                	$is_tree = true;
                	break;
                }
            }
            if (! $is_tree) {
                $this -> syntax = $code;
                $this -> get_tree();
            }
            $this -> text = '';
            foreach ($this -> syntax as $val) {
                $this -> text .= $val['str'];
            }
        } elseif ($code) {
        	$this -> text = $code;
        	$this -> parse();
        }
    }

    function get_tokens() {
        $length = strlen($this -> text);
        $tokens = array();
        $token_key = -1;
        $type_of_char = null;
        for ($i=0; $i < $length; ++$i) {
            $previous_type = $type_of_char;
            switch ($this -> text[$i]) {
                case '[':
                    $type_of_char = 0;
                    break;
                case ']':
                    $type_of_char = 1;
                    break;
                case '"':
                    $type_of_char = 2;
                    break;
                case "'":
                    $type_of_char = 3;
                    break;
                case "=":
                    $type_of_char = 4;
                    break;
                case '/':
                    $type_of_char = 5;
                    break;
                case ' ':
                    $type_of_char = 6;
                    break;
                case "\t":
                    $type_of_char = 6;
                    break;
                case "\n":
                    $type_of_char = 6;
                    break;
                case "\r":
                    $type_of_char = 6;
                    break;
                case "\0":
                    $type_of_char = 6;
                    break;
                case "\x0B":
                    $type_of_char = 6;
                    break;
                default:
                    $type_of_char = 7;
            }
            if (7 == $previous_type && $type_of_char != $previous_type) {
                $word = strtolower($tokens[$token_key][1]);
                if (isset($this -> tags[$word])) {
                    $tokens[$token_key][0] = 8;
                }
            }
            switch ($type_of_char) {
                case 6:
                    if (6 == $previous_type) {
                        $tokens[$token_key][1] .= $this -> text[$i];
                    } else {
                    	$tokens[++$token_key] = array(6, $this -> text[$i]);
                    }
                    break;
                case 7:
                    if (7 == $previous_type) {
                        $tokens[$token_key][1] .= $this -> text[$i];
                    } else {
                    	$tokens[++$token_key] = array(7, $this -> text[$i]);
                    }
                    break;
                default:
                    $tokens[++$token_key] = array(
                        $type_of_char, $this -> text[$i]
                    );
            }
        }
        return $tokens;
    }

    function parse($code = '') {
        if ($code) {
            $this -> do_bbcode($code);
            return;
        }
        /*
        Using the finite state machine method
        List of possible FSM states:
        0  - Start of scanning or outside of a tag. Expect anything.
        1  - Encountered the character "[", assumed to be the beginning of a tag. Expecting a tag name or the character "/".
        2  - Found an unexpected "[" character inside a tag. Consider the previous string an error. Expecting a tag name or the character "/".
        3  - Found a syntax error inside a tag. The current character is not "[". Expect anything.
        4  - Immediately after "[" found the character "/". Assuming it’s a closing tag. Expecting a tag name or the character "]".
        5  - Immediately after "[" found a tag name. Assuming it’s an opening tag. Expecting a space, "=", "/", or "]".
        6  - Found the tag-closing character "]". Expect anything.
        7  - Immediately after "[/" found a tag name. Expecting "]".
        8  - In an opening tag, found "=". Expecting a space or an attribute value.
        9  - In an opening tag, found "/", indicating tag closure. Expecting "]".
        10 - In an opening tag, found a space after the tag name or attribute name. Expecting "=", another attribute name, "/", or "]".
        11 - Found '"' indicating the start of an attribute value enclosed in double quotes. Expect anything.
        12 - Found "'" indicating the start of an attribute value enclosed in single quotes. Expect anything.
        13 - Found the start of an unquoted attribute value. Expect anything.
        14 - In an opening tag, found a space after "=". Expecting an attribute value.
        15 - Found an attribute name. Expecting a space, "=", "/", or "]".
        16 - Inside an attribute value enclosed in double quotes. Expect anything.
        17 - End of an attribute value. Expecting a space, another attribute name, "/", or "]".
        18 - Inside an attribute value enclosed in single quotes. Expect anything.
        19 - Inside an unquoted attribute value. Expect anything.
        20 - Found a space after an attribute value. Expecting another attribute name, "/", or "]".

        Description of the finite state machine:
*/
        $finite_automaton = array(
               //  Previous   |   States for current events   |
               //  States    |  0 |  1 |  2 |  3 |  4 |  5 |  6 |  7 |  8 |
                   0 => array(  1 ,  0 ,  0 ,  0 ,  0 ,  0 ,  0 ,  0 ,  0 )
                ,  1 => array(  2 ,  3 ,  3 ,  3 ,  3 ,  4 ,  3 ,  3 ,  5 )
                ,  2 => array(  2 ,  3 ,  3 ,  3 ,  3 ,  4 ,  3 ,  3 ,  5 )
                ,  3 => array(  1 ,  0 ,  0 ,  0 ,  0 ,  0 ,  0 ,  0 ,  0 )
                ,  4 => array(  2 ,  6 ,  3 ,  3 ,  3 ,  3 ,  3 ,  3 ,  7 )
                ,  5 => array(  2 ,  6 ,  3 ,  3 ,  8 ,  9 , 10 ,  3 ,  3 )
                ,  6 => array(  1 ,  0 ,  0 ,  0 ,  0 ,  0 ,  0 ,  0 ,  0 )
                ,  7 => array(  2 ,  6 ,  3 ,  3 ,  3 ,  3 ,  3 ,  3 ,  3 )
                ,  8 => array( 13 , 13 , 11 , 12 , 13 , 13 , 14 , 13 , 13 )
                ,  9 => array(  2 ,  6 ,  3 ,  3 ,  3 ,  3 ,  3 ,  3 ,  3 )
                , 10 => array(  2 ,  6 ,  3 ,  3 ,  8 ,  9 ,  3 , 15 , 15 )
                , 11 => array( 16 , 16 , 17 , 16 , 16 , 16 , 16 , 16 , 16 )
                , 12 => array( 18 , 18 , 18 , 17 , 18 , 18 , 18 , 18 , 18 )
                , 13 => array( 19 ,  6 , 19 , 19 , 19 , 19 , 17 , 19 , 19 )
                , 14 => array(  2 ,  3 , 11 , 12 , 13 , 13 ,  3 , 13 , 13 )
                , 15 => array(  2 ,  6 ,  3 ,  3 ,  8 ,  9 , 10 ,  3 ,  3 )
                , 16 => array( 16 , 16 , 17 , 16 , 16 , 16 , 16 , 16 , 16 )
                , 17 => array(  2 ,  6 ,  3 ,  3 ,  3 ,  9 , 20 , 15 , 15 )
                , 18 => array( 18 , 18 , 18 , 17 , 18 , 18 , 18 , 18 , 18 )
                , 19 => array( 19 ,  6 , 19 , 19 , 19 , 19 , 20 , 19 , 19 )
                , 20 => array(  2 ,  6 ,  3 ,  3 ,  3 ,  9 ,  3 , 15 , 15 )
            );
        // Finished describing the finite state machine
        $mode = 0;
        $result = array();
        $tag_decomposition = array();
        $token_key = -1;
        $value = '';
        // Scanning the array using the constructed finite state machine:
        foreach ($this -> get_tokens() as $token) {
            $previous_mode = $mode;
            $mode = $finite_automaton[$previous_mode][$token[0]];
            switch ($mode) {
                case 0:
                    if (-1 < $token_key && 'text'==$result[$token_key]['type']) {
                        $result[$token_key]['str'] .= $token[1];
                    } else {
                        $result[++$token_key] = array(
                                'type' => 'text',
                                'str' => $token[1]
                            );
                    }
                    break;
                case 1:
                    $tag_decomposition['name']     = '';
                    $tag_decomposition['type']     = '';
                    $tag_decomposition['str']      = '[';
                    $tag_decomposition['layout'][] = array( 0, '[' );
                    break;
                case 2:
                    if (-1<$token_key && 'text'==$result[$token_key]['type']) {
                        $result[$token_key]['str'] .= $tag_decomposition['str'];
                    } else {
                        $result[++$token_key] = array(
                                'type' => 'text',
                                'str' => $tag_decomposition['str']
                            );
                    }
                    $tag_decomposition = array();
                    $tag_decomposition['name']     = '';
                    $tag_decomposition['type']     = '';
                    $tag_decomposition['str']      = '[';
                    $tag_decomposition['layout'][] = array( 0, '[' );
                    break;
                case 3:
                    if (-1<$token_key && 'text'==$result[$token_key]['type']) {
                        $result[$token_key]['str'] .= $tag_decomposition['str'];
                        $result[$token_key]['str'] .= $token[1];
                    } else {
                        $result[++$token_key] = array(
                                'type' => 'text',
                                'str' => $tag_decomposition['str'].$token[1]
                            );
                    }
                    $tag_decomposition = array();
                    break;
                case 4:
                    $tag_decomposition['type'] = 'close';
                    $tag_decomposition['str'] .= '/';
                    $tag_decomposition['layout'][] = array( 1, '/' );
                    break;
                case 5:
                    $tag_decomposition['type'] = 'open';
                    $name = strtolower($token[1]);
                    $tag_decomposition['name'] = $name;
                    $tag_decomposition['str'] .= $token[1];
                    $tag_decomposition['layout'][] = array( 2, $token[1] );
                    $tag_decomposition['attrib'][$name] = '';
                    break;
                case 6:
                    if (! isset($tag_decomposition['name'])) {
                        $tag_decomposition['name'] = '';
                    }
                    if (13 == $previous_mode || 19 == $previous_mode) {
                        $tag_decomposition['layout'][] = array( 7, $value );
                    }
                    $tag_decomposition['str'] .= ']';
                    $tag_decomposition['layout'][] = array( 0, ']' );
                    $result[++$token_key] = $tag_decomposition;
                    $tag_decomposition = array();
                    break;
                case 7:
                    $tag_decomposition['name'] = strtolower($token[1]);
                    $tag_decomposition['str'] .= $token[1];
                    $tag_decomposition['layout'][] = array( 2, $token[1] );
                    break;
                case 8:
                    $tag_decomposition['str'] .= '=';
                    $tag_decomposition['layout'][] = array( 3, '=' );
                    break;
                case 9:
                    $tag_decomposition['type'] = 'open/close';
                    $tag_decomposition['str'] .= '/';
                    $tag_decomposition['layout'][] = array( 1, '/' );
                    break;
                case 10:
                    $tag_decomposition['str'] .= $token[1];
                    $tag_decomposition['layout'][] = array( 4, $token[1] );
                    break;
                case 11:
                    $tag_decomposition['str'] .= '"';
                    $tag_decomposition['layout'][] = array( 5, '"' );
                    $value = '';
                    break;
                case 12:
                    $tag_decomposition['str'] .= "'";
                    $tag_decomposition['layout'][] = array( 5, "'" );
                    $value = '';
                    break;
                case 13:
                    $tag_decomposition['attrib'][$name] = $token[1];
                    $value = $token[1];
                    $tag_decomposition['str'] .= $token[1];
                    break;
                case 14:
                    $tag_decomposition['str'] .= $token[1];
                    $tag_decomposition['layout'][] = array( 4, $token[1] );
                    break;
                case 15:
                    $name = strtolower($token[1]);
                    $tag_decomposition['str'] .= $token[1];
                    $tag_decomposition['layout'][] = array( 6, $token[1] );
                    $tag_decomposition['attrib'][$name] = '';
                    break;
                case 16:
                    $tag_decomposition['str'] .= $token[1];
                    $tag_decomposition['attrib'][$name] .= $token[1];
                    $value .= $token[1];
                    break;
                case 17:
                    $tag_decomposition['str'] .= $token[1];
                    $tag_decomposition['layout'][] = array( 7, $value );
                    $value = '';
                    $tag_decomposition['layout'][] = array( 5, $token[1] );
                    break;
                case 18:
                    $tag_decomposition['str'] .= $token[1];
                    $tag_decomposition['attrib'][$name] .= $token[1];
                    $value .= $token[1];
                    break;
                case 19:
                    $tag_decomposition['str'] .= $token[1];
                    $tag_decomposition['attrib'][$name] .= $token[1];
                    $value .= $token[1];
                    break;
                case 20:
                    $tag_decomposition['str'] .= $token[1];
                    if ( 13 == $previous_mode || 19 == $previous_mode ) {
                        $tag_decomposition['layout'][] = array( 7, $value );
                    }
                    $value = '';
                    $tag_decomposition['layout'][] = array( 4, $token[1] );
                    break;
            }
        }
        if (count($tag_decomposition)) {
            if ( -1 < $token_key && 'text' == $result[$token_key]['type'] ) {
                $result[$token_key]['str'] .= $tag_decomposition['str'];
            } else {
                $result[++$token_key] = array(
                        'type' => 'text',
                        'str' => $tag_decomposition['str']
                    );
            }
        }
        $this -> syntax = $result;
        $this -> get_tree();
        return $result;
    }

    function specialchars($string) {
        $chars = array(
            '[' => '@l;',
            ']' => '@r;',
            '"' => '@q;',
            "'" => '@a;',
            '@' => '@at;'
        );
        return strtr($string, $chars);
    }

    function unspecialchars($string) {
        $chars = array(
            '@l;'  => '[',
            '@r;'  => ']',
            '@q;'  => '"',
            '@a;'  => "'",
            '@at;' => '@'
        );
        return strtr($string, $chars);
    }

    function must_close_tag($current, $next) {
        $class_vars = get_class_vars($this -> tags[$current]);
        $must_close = in_array($next, $class_vars['ends']);
        $class_vars = get_class_vars($this -> tags[$next]);
        if (! $must_close && isset($class_vars['stop'])) {
            $must_close = in_array($current, $class_vars['stop']);
        }
        return $must_close;
    }

    function normalize_bracket($syntax) {
        $structure = array();
        $structure_key = -1;
        $level = 0;
        $open_tags = array();
        foreach ($syntax as $syntax_key => $val) {
            unset($val['layout']);
            switch ($val['type']) {
                case 'text':
                    $val['str'] = $this -> unspecialchars($val['str']);
                    $type = (-1 < $structure_key)
                        ? $structure[$structure_key]['type'] : false;
                    if ('text' == $type) {
                        $structure[$structure_key]['str'] .= $val['str'];
                    } else {
                        $structure[++$structure_key] = $val;
                        $structure[$structure_key]['level'] = $level;
                    }
                    break;
                case 'open/close':
                    $val['attrib'] = array_map(
            	        array(&$this, 'unspecialchars'), $val['attrib']
            	    );
                    foreach (array_reverse($open_tags,true) as $ult_key => $ultimate) {
                        if ($this -> must_close_tag($ultimate, $val['name'])) {
                            $structure[++$structure_key] = array(
                                    'type'  => 'close',
                                    'name'  => $ultimate,
                                    'str'   => '',
                                    'level' => --$level
                                );
                            unset($open_tags[$ult_key]);
                        } else {
                        	break;
                        }
                    }
                    $structure[++$structure_key] = $val;
                    $structure[$structure_key]['level'] = $level;
                    break;
                case 'open':
                    $val['attrib'] = array_map(
            	        array(&$this, 'unspecialchars'), $val['attrib']
            	    );
                    foreach (array_reverse($open_tags,true) as $ult_key => $ultimate) {
                        if ($this -> must_close_tag($ultimate, $val['name'])) {
                            $structure[++$structure_key] = array(
                                    'type'  => 'close',
                                    'name'  => $ultimate,
                                    'str'   => '',
                                    'level' => --$level
                                );
                            unset($open_tags[$ult_key]);
                        } else { break; }
                    }
                    $class_vars = get_class_vars($this -> tags[$val['name']]);
                    if ($class_vars['is_close']) {
                        $val['type'] = 'open/close';
                        $structure[++$structure_key] = $val;
                        $structure[$structure_key]['level'] = $level;
                    } else {
                        $structure[++$structure_key] = $val;
                        $structure[$structure_key]['level'] = $level++;
                        $open_tags[] = $val['name'];
                    }
                    break;
                case 'close':
                    if (! count($open_tags)) {
                        $type = (-1 < $structure_key)
                            ? $structure[$structure_key]['type'] : false;
                        if ( 'text' == $type ) {
                            $structure[$structure_key]['str'] .= $val['str'];
                        } else {
                            $structure[++$structure_key] = array(
                                    'type'  => 'text',
                                    'str'   => $val['str'],
                                    'level' => 0
                                );
                        }
                        break;
                    }
                    if (! $val['name']) {
                        end($open_tags);
                        list($ult_key, $ultimate) = each($open_tags);
                        $val['name'] = $ultimate;
                        $structure[++$structure_key] = $val;
                        $structure[$structure_key]['level'] = --$level;
                        unset($open_tags[$ult_key]);
                        break;
                    }
                    if (! in_array($val['name'],$open_tags)) {
                        $type = (-1 < $structure_key)
                            ? $structure[$structure_key]['type'] : false;
                        if ('text' == $type) {
                            $structure[$structure_key]['str'] .= $val['str'];
                        } else {
                            $structure[++$structure_key] = array(
                                    'type'  => 'text',
                                    'str'   => $val['str'],
                                    'level' => $level
                                );
                        }
                        break;
                    }
                    foreach (array_reverse($open_tags,true) as $ult_key => $ultimate) {
                        if ($ultimate != $val['name']) {
                            $structure[++$structure_key] = array(
                                    'type'  => 'close',
                                    'name'  => $ultimate,
                                    'str'   => '',
                                    'level' => --$level
                                );
                            unset($open_tags[$ult_key]);
                        } else {
                        	break;
                        }
                    }
                    $structure[++$structure_key] = $val;
                    $structure[$structure_key]['level'] = --$level;
                    unset($open_tags[$ult_key]);
            }
        }
        foreach (array_reverse($open_tags,true) as $ult_key => $ultimate) {
            $structure[++$structure_key] = array(
                    'type'  => 'close',
                    'name'  => $ultimate,
                    'str'   => '',
                    'level' => --$level
                );
            unset($open_tags[$ult_key]);
        }
        return $structure;
    }

    function get_tree() {
        /* Converting $this -> syntax into the correct bracket structure */
        $structure = $this -> normalize_bracket($this -> syntax);
        /* Tracking whether elements have unresolved subelements.
            Adjusting $structure accordingly. */
        $normalized = array();
        $normal_key = -1;
        $level = 0;
        $open_tags = array();
        $not_tags = array();
        foreach ($structure as $structure_key => $val) {
            switch ($val['type']) {
                case 'text':
                    $type = (-1 < $normal_key)
                        ? $normalized[$normal_key]['type'] : false;
                    if ('text' == $type) {
                        $normalized[$normal_key]['str'] .= $val['str'];
                    } else {
                        $normalized[++$normal_key] = $val;
                        $normalized[$normal_key]['level'] = $level;
                    }
                    break;
                case 'open/close':
                    $is_open = count($open_tags);
                    end($open_tags);
                    $info = get_class_vars($this -> tags[$val['name']]);
                    if ($is_open) {
                        $class_vars = get_class_vars(
                            $this -> tags[current($open_tags)]
                        );
                        $children = $class_vars['children'];
                    } else {
                        $children = array();
                    }
                    if (isset($info['top_level'])) {
                        $top_level = $info['top_level'];
                    } else {
                        $top_level = in_array($val['name'], $this -> children);
                    }
                    $is_child = in_array($val['name'], $children);
                    if (isset($info['parent']) && ! $is_child) {
                        if (in_array(current($open_tags), $info['parent'])) {
                            $is_child = true;
                        }
                    }
                    if (! $level && ! $top_level || $is_open && ! $is_child) {
                        $type = (-1 < $normal_key)
                            ? $normalized[$normal_key]['type'] : false;
                        if ( 'text' == $type ) {
                            $normalized[$normal_key]['str'] .= $val['str'];
                        } else {
                            $normalized[++$normal_key] = array(
                                    'type'  => 'text',
                                    'str'   => $val['str'],
                                    'level' => $level
                                );
                        }
                        break;
                    }
                    $normalized[++$normal_key] = $val;
                    $normalized[$normal_key]['level'] = $level;
                    break;
                case 'open':
                    $is_open = count($open_tags);
                    end($open_tags);
                    $info = get_class_vars($this -> tags[$val['name']]);
                    if ($is_open) {
                        $class_vars = get_class_vars(
                            $this -> tags[current($open_tags)]
                        );
                        $children = $class_vars['children'];
                    } else {
                        $children = array();
                    }
                    if (isset($info['top_level'])) {
                        $top_level = $info['top_level'];
                    } else {
                        $top_level = in_array($val['name'], $this -> children);
                    }
                    $is_child = in_array($val['name'], $children);
                    if (isset($info['parent']) && ! $is_child) {
                        if (in_array(current($open_tags), $info['parent'])) {
                            $is_child = true;
                        }
                    }
                    if (! $level && ! $top_level || $is_open && ! $is_child) {
                        $not_tags[$val['level']] = $val['name'];
                        $type = (-1 < $normal_key)
                            ? $normalized[$normal_key]['type'] : false;
                        if ( 'text' == $type ) {
                            $normalized[$normal_key]['str'] .= $val['str'];
                        } else {
                            $normalized[++$normal_key] = array(
                                    'type'  => 'text',
                                    'str'   => $val['str'],
                                    'level' => $level
                                );
                        }
                        break;
                    }
                    $normalized[++$normal_key] = $val;
                    $normalized[$normal_key]['level'] = $level++;
                    $ult_key = count($open_tags);
                    $open_tags[$ult_key] = $val['name'];
                    break;
                case 'close':
                    $not_normal = isset($not_tags[$val['level']])
                        && $not_tags[$val['level']] = $val['name'];
                    if ( $not_normal ) {
                        unset($not_tags[$val['level']]);
                        $type = (-1 < $normal_key)
                            ? $normalized[$normal_key]['type'] : false;
                        if ( 'text' == $type ) {
                            $normalized[$normal_key]['str'] .= $val['str'];
                        } else {
                            $normalized[++$normal_key] = array(
                                    'type'  => 'text',
                                    'str'   => $val['str'],
                                    'level' => $level
                                );
                        }
                        break;
                    }
                    $normalized[++$normal_key] = $val;
                    $normalized[$normal_key]['level'] = --$level;
                    $ult_key = count($open_tags) - 1;
                    unset($open_tags[$ult_key]);
                    break;
            }
        }
        unset($structure);
        // Building the element tree
        $result = array();
        $result_key = -1;
        $open_tags = array();
        $val_key = -1;
        foreach ($normalized as $normal_key => $val) {
            switch ($val['type']) {
                case 'text':
                    if (! $val['level']) {
                        $result[++$result_key] = array(
                                'type' => 'text',
                                'str' => $val['str']
                            );
                        break;
                    }
                    $open_tags[$val['level']-1]['val'][] = array(
                            'type' => 'text',
                            'str' => $val['str']
                        );
                    break;
                case 'open/close':
                    if (! $val['level']) {
                        $result[++$result_key] = array(
                                'type'   => 'item',
                                'name'   => $val['name'],
                                'attrib' => $val['attrib'],
                                'val'    => array()
                            );
                        break;
                    }
                    $open_tags[$val['level']-1]['val'][] = array(
                            'type'   => 'item',
                            'name'   => $val['name'],
                            'attrib' => $val['attrib'],
                            'val'    => array()
                        );
                    break;
                case 'open':
                    $open_tags[$val['level']] = array(
                            'type'   => 'item',
                            'name'   => $val['name'],
                            'attrib' => $val['attrib'],
                            'val'    => array()
                        );
                    break;
                case 'close':
                    if ( ! $val['level'] ) {
                        $result[++$result_key] = $open_tags[0];
                        unset($open_tags[0]);
                        break;
                    }
                    $open_tags[$val['level']-1]['val'][] = $open_tags[$val['level']];
                    unset($open_tags[$val['level']]);
                    break;
            }
        }
        $this -> tree = $result;
        return $result;
    }

    function get_syntax($tree = false) {
        if (! is_array($tree)) {
            $tree = $this -> tree;
        }
        $syntax = array();
        foreach ($tree as $elem) {
            if ('text' == $elem['type']) {
            	$syntax[] = array(
            	    'type' => 'text',
            	    'str' => $this -> specialchars($elem['str'])
            	);
            } else {
                $sub_elems = $this -> get_syntax($elem['val']);
                $str = '';
                $layout = array(array(0, '['));
                foreach ($elem['attrib'] as $name => $val) {
                    $val = $this -> specialchars($val);
                    if ($str) {
                    	$str .= ' ';
                    	$layout[] = array(4, ' ');
                    	$layout[] = array(6, $name);
                    } else {
                        $layout[] = array(2, $name);
                    }
                    $str .= $name;
                    if ($val) {
                    	$str .= '="'.$val.'"';
                    	$layout[] = array(3, '=');
                    	$layout[] = array(5, '"');
                    	$layout[] = array(7, $val);
                    	$layout[] = array(5, '"');
                    }
                }
                if (count($sub_elems)) {
                	$str = '['.$str.']';
                } else {
                    $str = '['.$str.' /]';
                    $layout[] = array(4, ' ');
                    $layout[] = array(1, '/');
                }
                $layout[] = array(0, ']');
                $syntax[] = array(
                    'type' => count($sub_elems) ? 'open' : 'open/close',
                    'str' => $str,
                    'name' => $elem['name'],
                    'attrib' => $elem['attrib'],
                    'layout' => $layout
                );
                foreach ($sub_elems as $sub_elem) { $syntax[] = $sub_elem; }
                if (count($sub_elems)) {
                	$syntax[] = array(
                	    'type' => 'close',
                	    'str' => '[/'.$elem['name'].']',
                	    'name' => $elem['name'],
                	    'layout' => array(
                	        array(0, '['),
                	        array(1, '/'),
                	        array(2, $elem['name']),
                	        array(0, ']')
                	    )
                	);
                }
            }
        }
        return $syntax;
    }

    function replace_links($text) {
        $text = nl2br(htmlspecialchars($text,ENT_NOQUOTES));
        $text = str_replace('  ', '&nbsp;&nbsp;', $text);
        if ($this -> autolinks) {
            $uri = "[\w\d-]+\.[\w\d-]+[^\s<\"\']*[^.,;\s<\"\'\)]+";
            $search = array(
                "'(.)((http|https|ftp)://".$uri.")'si",
                "'([^/])(www\.".$uri.")'si",
                // https://stackoverflow.com/questions/24764212/preg-match-compilation-failed-invalid-range-in-character-class-at-offset
                "'([^\w\d\-.])([\w\d\-.]+@[\w\d\-.]+\.[\w]+[^.,;\s<\"\'\)]+)'si"
            );
            $replace = array(
                '$1<a href="$2" target="_blank">$2</a>',
                '$1<a href="http://$2" target="_blank">$2</a>',
                '$1<a href="mailto:$2">$2</a>'
            );
            $text = preg_replace($search, $replace, $text);
        }
        foreach ($this -> mnemonics as $mnemonic => $value) {
            $text = str_replace($mnemonic, $value, $text);
        }
        return $text;
    }

    function get_html($elems = false) {
        if (! is_array($elems)) {
            $elems = $this -> tree;
        }
        $result = '';
        $lbr = 0;
        $rbr = 0;
        foreach ($elems as $elem) {
            if ('text' == $elem['type']) {
                $elem['str'] = $this -> replace_links($elem['str']);
                for ($i=0; $i < $rbr; ++$i) {
                    $elem['str'] = ltrim($elem['str']);
                    if ('<br />' == substr($elem['str'], 0, 6)) {
                        $elem['str'] = substr_replace($elem['str'], '', 0, 6);
                    }
                }
                $result .= $elem['str'];
            } else {
                $class_vars = get_class_vars($this -> tags[$elem['name']]);
                $lbr = $class_vars['lbr'];
                $rbr = $class_vars['rbr'];
                for ($i=0; $i < $lbr; ++$i) {
                    $result = rtrim($result);
                    if ('<br />' == substr($result, -6)) {
                        $result = substr_replace($result, '', -6, 6);
                    }
                }
                $handler = $this -> tags[$elem['name']];
                if (class_exists($handler)) {
                    $tag = new $handler;
                    $tag -> tag = $elem['name'];
                    $tag -> attrib = $elem['attrib'];
                    $tag -> tags = $this -> tags;
                    $tag -> mnemonics = $this -> mnemonics;
                    $tag -> autolinks = $this -> autolinks;
                    $tag -> tree = $elem['val'];
                    $result .= $tag -> get_html();
                } else {
                    $result .= bbcode::get_html($elem['val']);
                }
            }
        }
        return $result;
    }
}

// Class for the [a], [anchor], and [url] tags
class bb_a extends bbcode {
    public $ends = array(
        '*','align','center','h1','h2','h3','hr','justify','left','list','php',
        'quote','right','table','td','th','tr'
    );
    public $children = array(
        'abbr','acronym','b','bbcode','code','color','font','i','img','nobb',
        's','size','strike','sub','sup','tt','u'
    );
    function get_html($elems = false) {
        $text = '';
        foreach ($this -> tree as $val) {
            if ('text' == $val['type']) { $text .= $val['str']; }
        }
        $href = '';
        if (isset($this -> attrib['url'])) {
            $href = $this -> attrib['url'];
        }
        if (! $href && isset($this -> attrib['a'])) {
            $href = $this -> attrib['a'];
        }
        if (! $href && isset($this -> attrib['href'])) {
            $href = $this -> attrib['href'];
        }
        if (! $href && ! isset($this -> attrib['anchor'])) { $href = $text; }
        $protocols = array(
            'http://',  'https://',  'ftp://',  'file://',  'mailto:',
            '#',        '/',         '?',       './',       '../'
        );
        $is_http = false;
        foreach ($protocols as $val) {
            if ($val == substr($href, 0, strlen($val))) {
                $is_http = true;
                break;
            }
        }
        if ($href && ! $is_http) { $href = 'http://'.$href; }
        $attr = 'class="bb"';
        if ($href) {
        	$attr .= ' href="'.htmlspecialchars($href).'"';
        }
        if (isset($this -> attrib['title'])) {
            $title = $this -> attrib['title'];
            $attr .= ' title="'.htmlspecialchars($title).'"';
        }
        $id = '';
        if (isset($this -> attrib['name'])) {
            $id = $this -> attrib['name'];
        }
        if (isset($this -> attrib['id'])) {
            $id = $this -> attrib['id'];
        }
        if (isset($this -> attrib['anchor'])) {
            $id = $this -> attrib['anchor'];
            if (! $id) { $id = $text; }
        }
        if ($id) {
        	if ($id[0] < 'A' || $id[0] > 'z') { $id = 'bb'.$id; }
        	$attr .= ' id="'.htmlspecialchars($id).'"';
        }
        if (isset($this -> attrib['target'])) {
            $target = $this -> attrib['target'];
            $attr .= ' target="'.htmlspecialchars($target).'"';
        }
        return '<a '.$attr.'>'.parent::get_html($this -> tree).'</a>';
    }
}

// Class for the [align], [center], [justify], [left], and [right] tags
class bb_align extends bbcode {
    public $rbr = 1;
    public $ends = array('*','tr','td','th');
    function get_html($elems = false) {
        $align = '';
        if (isset($this -> attrib['justify'])) { $align = 'justify'; }
        if (isset($this -> attrib['left'])) { $align = 'left'; }
        if (isset($this -> attrib['right'])) { $align = 'right'; }
        if (isset($this -> attrib['center'])) { $align = 'center'; }
        if (! $align && isset($this -> attrib['align'])) {
            switch (strtolower($this -> attrib['align'])) {
                case 'left':
                    $align = 'left';
                    break;
                case 'right':
                    $align = 'right';
                    break;
                case 'center':
                    $align = 'center';
                    break;
                case 'justify':
                    $align = 'justify';
                    break;
            }
        }
        return '<div class="bb" align="'.$align.'">'
            .parent::get_html($this -> tree).'</div>';
    }
}

// Class for the [color] tag
class bb_color extends bbcode {
    public $ends = array(
        '*','address','align','center','h1','h2','h3','hr','justify',
        'left','list','php','quote','right','table','td','th','tr'
    );
    public $children = array(
        'a','abbr','acronym','anchor','b','bbcode','code','color','email',
        'font','google','i','img','nobb','s','size','strike','sub','sup','tt',
        'u','url'
    );
    function get_html($elems = false) {
        $color = htmlspecialchars($this -> attrib['color']);
        return '<font color="'.$color.'">'.parent::get_html($this -> tree)
            .'</font>';
    }
}

// Class for the [s] and [strike] tags
class bb_del extends bbcode {
    public $ends = array(
        '*','address','align','center','h1','h2','h3','hr','justify',
        'left','list','php','quote','right','table','td','th','tr'
    );
    public $children = array(
        'a','abbr','acronym','anchor','b','bbcode','code','color','email',
        'font','google','i','img','nobb','s','size','strike','sub','sup','tt',
        'u','url'
    );
    function get_html($elems = false) {
        return '<del>'.parent::get_html($this -> tree).'</del>';
    }
}

// Class for the [email] tag
class bb_email extends bbcode {
    public $ends = array(
        '*','address','align','center','h1','h2','h3','hr','justify',
        'left','list','php','quote','right','table','td','th','tr'
    );
    public $children = array(
        'abbr','acronym','b','bbcode','code','color','email','font','i','img',
        'nobb','s','size','strike','sub','sup','tt','u'
    );
    function get_html($elems = false) {
        $attr = ' class="bb_email"';
        $href = $this -> attrib['email'];
        if (! $href) {
            foreach ($this -> tree as $text) {
                if ('text' == $text['type']) { $href .= $text['str']; }
            }
        }
        $protocols = array('mailto:');
        $is_http = false;
        foreach ($protocols as $val) {
            if ($val == substr($href,0,strlen($val))) {
                $is_http = true;
                break;
            }
        }
        if (! $is_http) { $href = 'mailto:'.$href; }
        if ($href) { $attr .= ' href="'.htmlspecialchars($href).'"'; }
        $title = isset($this -> attrib['title']) ? $this -> attrib['title'] : '';
        if ($title) { $attr .= ' title="'.htmlspecialchars($title).'"'; }
        $name = isset($this -> attrib['name']) ? $this -> attrib['name'] : '';
        if ($name) { $attr .= ' name="'.htmlspecialchars($name).'"'; }
        $target = isset($this -> attrib['target']) ? $this -> attrib['target'] : '';
        if ($target) { $attr .= ' target="'.htmlspecialchars($target).'"'; }
        return '<a'.$attr.'>'.parent::get_html($this -> tree).'</a>';
    }
}

// Class for the [font]
class bb_font extends bbcode {
    public $ends = array(
        '*','address','align','center','h1','h2','h3','hr','justify',
        'left','list','php','quote','right','table','td','th','tr'
    );
    public $children = array(
        'a','abbr','acronym','anchor','b','bbcode','code','color','email',
        'font','font','google','i','img','nobb','s','size','strike','sub','sup',
        'tt','u','url'
    );
    function get_html($elems = false) {
        $face = $this -> attrib['font'];
        $attr = ' face="'.htmlspecialchars($face).'"';
        $color = isset($this -> attrib['color']) ? $this -> attrib['color'] : '';
        if ($color) { $attr .= ' color="'.htmlspecialchars($color).'"'; }
        $size = isset($this -> attrib['size']) ? $this -> attrib['size'] : '';
        if ($size) { $attr .= ' size="'.htmlspecialchars($size).'"'; }
        return '<font'.$attr.'>'.parent::get_html($this -> tree).'</font>';
    }
}

// Class for the [hr]
class bb_hr extends bbcode {
    public $isClose = true;
    public $rbr = 1;
    public $ends = array();
    public $children = array();
    function get_html($elems = false) {
        return '<hr class="bb" />';
    }
}

// Class for the [i]
class bb_i extends bbcode {
    public $ends = array(
        '*','address','align','center','h1','h2','h3','hr','justify',
        'left','list','php','quote','right','table','td','th','tr'
    );
    public $children = array(
        'a','abbr','acronym','anchor','b','bbcode','code','color','email',
        'font','google','i','img','nobb','s','size','strike','sub','sup','tt',
        'u','url'
    );
    function get_html($elems = false) {
        return '<i>'.parent::get_html($this -> tree).'</i>';
    }
}

// Class for the [img]
class bb_img extends bbcode {
    public $ends = array();
    public $children = array();
    function get_html($elems = false) {
        $attr = 'alt=""';
        if (isset($this -> attrib['width'])) {
            $width = (int) $this -> attrib['width'];
            $attr .= $width ? ' width="'.$width.'"' : '';
        }
        if (isset($this -> attrib['height'])) {
            $height = (int) $this -> attrib['height'];
            $attr .= $height ? ' height="'.$height.'"' : '';
        }
        if (isset($this -> attrib['border'])) {
            $border = (int) $this -> attrib['border'];
            $attr .= ' border="'.$border.'"';
        }
        $src = '';
        foreach ($this -> tree as $text) {
            if ('text' == $text['type']) { $src .= $text['str']; }
        }
        $src = htmlentities($src, ENT_QUOTES);
        $src = str_replace('.', '&#'.ord('.').';', $src);
        $src = str_replace(':', '&#'.ord(':').';', $src);
        $src = str_replace('(', '&#'.ord('(').';', $src);
        $src = str_replace(')', '&#'.ord(')').';', $src);

        return "<img class=reloadimage title=$src src=pic.php?url=".$src." />";
//      return '<img src="'.$src.'" '.$attr.' />';
    }
}

// Class for the [quote]
class bb_quote extends bbcode {
    public $rbr = 1;
    public $ends = array();
    function get_html($elems = false) {
        $author = htmlspecialchars($this -> attrib['quote']);
        if ($author) $author = "(\n<b style=\"color: white;\">".$author."</b>\n)";
        $author = "<div style=\"border: 3px double rgb(65, 86, 128); padding: 1px 4px 2px;\">\nЦитата ".$author." </div>";
        return $author."<div style=\"border-style: none double double; border-color: -moz-use-text-color rgb(65, 86, 128) rgb(65, 86, 128); border-width: medium 3px 3px; padding: 4px 4px 6px;\">".parent::get_html($this -> tree)
            ."</div>";
    }
}

// Class for the [size]
class bb_size extends bbcode {
    public $ends = array(
        '*','address','align','center','h1','h2','h3','hr','justify',
        'left','list','php','quote','right','table','td','th','tr'
    );
    public $children = array(
        'a','abbr','acronym','anchor','b','bbcode','code','color','email',
        'font','google','i','img','nobb','s','size','strike','sub','sup','tt',
        'u','url'
    );
    function get_html($elems = false) {
        $sign = '';
        if (strlen($this -> attrib['size'])) {
            $sign = $this -> attrib['size'][0];
        }
        if ('+' != $sign) { $sign = ''; }
        $size = (int) $this -> attrib['size'];
        if (7 < $size) {
        	$size = 7;
        	$sign = '';
        }
        if (-6 > $size) {
            $size = '-6';
        	$sign = '';
        }
        if (0 == $size) {
            $size = 3;
        }
        $size = $sign.$size;
        return '<font size="'.$size.'">'.parent::get_html($this -> tree).'</font>';
    }
}

// Class for the [b]
class bb_strong extends bbcode {
    public $ends = array(
        '*','address','align','center','h1','h2','h3','hr','justify',
        'left','list','php','quote','right','table','td','th','tr'
    );
    public $children = array(
        'a','abbr','acronym','anchor','b','bbcode','code','color','email',
        'font','google','i','img','nobb','s','size','strike','sub','sup','tt',
        'u','url'
    );
    function get_html($elems = false) {
        return '<strong>'.parent::get_html($this -> tree).'</strong>';
    }
}

// Class for the [sub]
class bb_sub extends bbcode {
    public $ends = array(
        '*','address','align','center','h1','h2','h3','hr','justify',
        'left','list','php','quote','right','table','td','th','tr'
    );
    public $children = array(
        'a','abbr','acronym','anchor','b','bbcode','code','color','email',
        'font','google','i','img','nobb','s','size','strike','sub','sup','tt',
        'u','url'
    );
    function get_html($elems = false) {
        return '<sub>'.parent::get_html($this -> tree).'</sub>';
    }
}

// Class for the [sup]
class bb_sup extends bbcode {
    public $ends = array(
        '*','address','align','center','h1','h2','h3','hr','justify',
        'left','list','php','quote','right','table','td','th','tr'
    );
    public $children = array(
        'a','abbr','acronym','anchor','b','bbcode','code','color','email',
        'font','google','i','img','nobb','s','size','strike','sub','sup','tt',
        'u','url'
    );
    function get_html($elems = false) {
        return '<sup>'.parent::get_html($this -> tree).'</sup>';
    }
}

// Class for the [u]
class bb_u extends bbcode {
    public $ends = array(
        '*','address','align','center','h1','h2','h3','hr',
        'justify','left','list','php','quote','right','table','td','th','tr'
    );
    public $children = array(
        'a','abbr','acronym','anchor','b','bbcode','code','color','email',
        'font','google','i','img','nobb','s','size','strike','sub','sup','tt',
        'u','url'
    );
    function get_html($elems = false) {
        return '<u>'.parent::get_html($this -> tree).'</u>';
    }
}

// Convert BB codes to HTML.
function bb ($text)
{
    $bb = new bbcode ($text);
    return $bb->get_html ();
}

?>