<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Simplemdm_model::class, function (Faker\Generator $faker) {
    return [
        'simplemdm_id' => $faker->randomNumber(5),
        'device_name' => $faker->word() . "'s " . $faker->randomElement(['MacBook Pro', 'MacBook Air', 'iMac', 'Mac mini', 'Mac Pro', 'iPhone', 'iPad']),
        'status' => $faker->randomElement(['enrolled', 'enrolled', 'enrolled', 'unenrolled']),
        'enrolled_at' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d\TH:i:s.000P'),
        'last_seen_at' => $faker->dateTimeBetween('-7 days', 'now')->format('Y-m-d\TH:i:s.000P'),
        'last_seen_ip' => $faker->ipv4(),
        'model_name' => $faker->randomElement(['MacBook Pro (16-inch, 2023)', 'MacBook Air (M2, 2022)', 'iMac (24-inch, M1, 2021)', 'Mac mini (M2, 2023)', 'iPhone 15 Pro', 'iPad Air (5th generation)']),
        'os_version' => $faker->randomElement(['14.3', '14.2.1', '14.1', '13.6.1', '17.3', '17.2']),
        'build_version' => $faker->regexify('[0-9]{2}[A-Z][0-9]{3,4}[a-z]?'),
        'is_supervised' => $faker->boolean(80),
        'is_dep_enrollment' => $faker->boolean(70),
        'dep_enrolled' => $faker->boolean(65),
        'dep_assigned' => $faker->boolean(75),
        'filevault_enabled' => $faker->boolean(85),
        'firewall_enabled' => $faker->boolean(60),
        'sip_enabled' => $faker->boolean(95),
        'remote_desktop_enabled' => $faker->boolean(20),
        'activation_lock_enabled' => $faker->boolean(30),
        'passcode_compliant' => $faker->boolean(90),
        'device_capacity' => $faker->randomElement([128.0, 256.0, 512.0, 1024.0, 2048.0]),
        'available_device_capacity' => $faker->randomFloat(2, 10.0, 500.0),
        'battery_level' => $faker->numberBetween(0, 100) . '%',
        'assignment_group' => $faker->randomElement(['Engineering', 'Marketing', 'Sales', 'Design', 'IT', 'HR', 'Finance', 'Executive']),
    ];
});
