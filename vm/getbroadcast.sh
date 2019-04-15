#!/bin/sh
all=(${@//[!0-9]/ })
[ "${#all[@]}" != "8" ] && {
        echo "Usage: "
        echo "${0##*/} ip.ip.ip.ip/mask.mask.mask.mask"
        exit 1
}

get_addr () {
        if [ "$1" = "-b" ]; then
                op='|'; op1='^'; arg='255'
                shift
        else
                op='&'
        fi
        unset address
        while [ "$5" ]; do
                num=$(( $1 $op ($5 $op1 $arg) ))
                shift
                address="$address.$num"
        done
}

get_addr ${all[@]}
#echo -e "network:\t${address#.}"
get_addr -b ${all[@]}
echo -e "broadcast:\t${address#.}"
