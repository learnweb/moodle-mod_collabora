// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
define(['jquery'], function($) {
    function resizeIframe() {
        var $iframe = $('iframe.collabora-iframe');
        if (!$iframe.length) {
            return;
        }
        var viewheight = $(window).height();
        var frametop = $iframe.offset().top;
        var height = viewheight - frametop - 30;
        if (height < 300) {
            height = 300;
        }
        $iframe.attr('height', height);
    }

    return {
        init: function() {
            $(window).on('resize', resizeIframe);
            resizeIframe();
        }
    };
});