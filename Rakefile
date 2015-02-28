desc "Default task"
task :default => [:test]

desc "Run tests"
task :test => [:phpunit, :behat]

desc "Run PHPUnit tests"
task :phpunit do
  begin
    sh %{vendor/bin/phpunit --verbose -c tests/phpunit --coverage-html build/coverage --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml}
  rescue Exception
    exit 1
  end
end

desc "Run functional tests"
task :behat do
  begin
    sh %{vendor/bin/behat --strict --config tests/behat/behat.yml}
  rescue Exception
    exit 1
  end
end
