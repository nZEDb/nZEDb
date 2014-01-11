# Print out Memory, cpu and load using https://github.com/thewtex/tmux-mem-cpu-load
mem_app=$(php ../../../../nZEDbBase.php)/misc/update/nix/tmux/bin/tmux-mem-cpu-load
run_segment() {
type $mem_app >/dev/null 2>&1
if [ "$?" -ne 0 ]; then
    return
fi

stats=$($mem_app)
if [ -n "$stats" ]; then
    echo "$stats";
fi
return 0
}
