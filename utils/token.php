<?php
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}
