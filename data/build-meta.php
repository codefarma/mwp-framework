<?php
return <<<'JSON'
{
    "framework_version": "2.2.6",
    "framework_bundled": true,
    "tables": [
        {
            "name": "mwp_queued_tasks",
            "columns": {
                "task_id": {
                    "allow_null": false,
                    "auto_increment": true,
                    "binary": false,
                    "comment": "",
                    "decimals": null,
                    "default": null,
                    "length": 20,
                    "name": "task_id",
                    "type": "BIGINT",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_action": {
                    "allow_null": false,
                    "auto_increment": false,
                    "binary": false,
                    "collation": "utf8mb4_unicode_ci",
                    "comment": "",
                    "decimals": null,
                    "default": "",
                    "length": 56,
                    "name": "task_action",
                    "type": "VARCHAR",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_data": {
                    "allow_null": true,
                    "auto_increment": false,
                    "binary": false,
                    "collation": "utf8mb4_unicode_ci",
                    "comment": "",
                    "decimals": null,
                    "default": null,
                    "length": 0,
                    "name": "task_data",
                    "type": "MEDIUMTEXT",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_priority": {
                    "allow_null": false,
                    "auto_increment": false,
                    "binary": false,
                    "comment": "",
                    "decimals": null,
                    "default": "5",
                    "length": 1,
                    "name": "task_priority",
                    "type": "TINYINT",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_next_start": {
                    "allow_null": false,
                    "auto_increment": false,
                    "binary": false,
                    "comment": "",
                    "decimals": null,
                    "default": "0",
                    "length": 11,
                    "name": "task_next_start",
                    "type": "INT",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_running": {
                    "allow_null": false,
                    "auto_increment": false,
                    "binary": false,
                    "comment": "",
                    "decimals": null,
                    "default": "0",
                    "length": 1,
                    "name": "task_running",
                    "type": "TINYINT",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_last_start": {
                    "allow_null": false,
                    "auto_increment": false,
                    "binary": false,
                    "comment": "",
                    "decimals": null,
                    "default": "0",
                    "length": 11,
                    "name": "task_last_start",
                    "type": "INT",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_last_iteration": {
                    "allow_null": false,
                    "auto_increment": false,
                    "binary": false,
                    "comment": "",
                    "decimals": null,
                    "default": "0",
                    "length": 11,
                    "name": "task_last_iteration",
                    "type": "INT",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_tag": {
                    "allow_null": true,
                    "auto_increment": false,
                    "binary": false,
                    "collation": "utf8mb4_unicode_ci",
                    "comment": "",
                    "decimals": null,
                    "default": null,
                    "length": 255,
                    "name": "task_tag",
                    "type": "VARCHAR",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_fails": {
                    "allow_null": false,
                    "auto_increment": false,
                    "binary": false,
                    "comment": "",
                    "decimals": null,
                    "default": "0",
                    "length": 2,
                    "name": "task_fails",
                    "type": "TINYINT",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_blog_id": {
                    "allow_null": false,
                    "auto_increment": false,
                    "binary": false,
                    "comment": "",
                    "decimals": null,
                    "default": "0",
                    "length": 11,
                    "name": "task_blog_id",
                    "type": "INT",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                },
                "task_completed": {
                    "allow_null": false,
                    "auto_increment": false,
                    "binary": false,
                    "comment": "",
                    "decimals": null,
                    "default": "0",
                    "length": 11,
                    "name": "task_completed",
                    "type": "INT",
                    "unsigned": false,
                    "values": [],
                    "zerofill": false
                }
            },
            "indexes": {
                "PRIMARY": {
                    "type": "primary",
                    "name": "PRIMARY",
                    "length": [
                        null
                    ],
                    "columns": [
                        "task_id"
                    ]
                },
                "task_priority": {
                    "type": "key",
                    "name": "task_priority",
                    "length": [
                        null
                    ],
                    "columns": [
                        "task_priority"
                    ]
                },
                "task_next_start": {
                    "type": "key",
                    "name": "task_next_start",
                    "length": [
                        null
                    ],
                    "columns": [
                        "task_next_start"
                    ]
                },
                "task_last_start": {
                    "type": "key",
                    "name": "task_last_start",
                    "length": [
                        null
                    ],
                    "columns": [
                        "task_last_start"
                    ]
                },
                "task_fails": {
                    "type": "key",
                    "name": "task_fails",
                    "length": [
                        null
                    ],
                    "columns": [
                        "task_fails"
                    ]
                },
                "task_next_up": {
                    "type": "key",
                    "name": "task_next_up",
                    "length": [
                        null,
                        null,
                        null,
                        null,
                        null
                    ],
                    "columns": [
                        "task_blog_id",
                        "task_completed",
                        "task_running",
                        "task_next_start",
                        "task_fails"
                    ]
                },
                "task_action_tag": {
                    "type": "key",
                    "name": "task_action_tag",
                    "length": [
                        null,
                        191
                    ],
                    "columns": [
                        "task_action",
                        "task_tag"
                    ]
                },
                "task_blog_id": {
                    "type": "key",
                    "name": "task_blog_id",
                    "length": [
                        null
                    ],
                    "columns": [
                        "task_blog_id"
                    ]
                }
            }
        }
    ]
}
JSON;
