#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <stdbool.h>
#include <sys/stat.h> 
#include <fcntl.h>

int get_cleaned_str(char *str, char *cleaned_str)
{
    int len = strlen(str);
    char *c, *cc;
    bool quotes_detected;
    cc = cleaned_str;

    quotes_detected = 0;
    for (c = str; *c != 0; c++) {
        if (*c == '"' && !quotes_detected) {
            quotes_detected = 1;
            continue;
        }

        if (*c == '"' && quotes_detected) {
            quotes_detected = 0;
            continue;
        }

        if (!quotes_detected && (*c == ' ' || *c == '\t'))
            continue;
        *cc = *c;
        cc++;
    }
    return 0;
}

char *parse_str(char *str, char *var_name, char *var_value)
{
    char *comment, *eq, *end, *cleaned_end;
    char cleaned_str[256];
    int len;

    end = strchr(str, '\n');
    if (!end) {
        end = str + strlen(str);
        memcpy(cleaned_str, str, end - str);
        cleaned_str[end - str] = 0;
    } else {
        end++;
        memcpy(cleaned_str, str, end - str);
        cleaned_str[end - str - 1] = 0;
    }

    comment = strchr(cleaned_str, '#');
    if (comment) {
        *comment = 0;
    }

    len = strlen(cleaned_str);
    if (!len)
        return end;

    cleaned_end = cleaned_str + len;


    eq = strchr(cleaned_str, '=');
    if (!eq)
        return end;

    memcpy(var_name, cleaned_str, eq - cleaned_str);
    var_name[eq - cleaned_str] = 0;
    memcpy(var_value, eq + 1, cleaned_end - eq - 1);
    var_value[cleaned_end - eq - 1] = 0;
    return end;
}

int parse_config(char *config_file_name, void *priv, void (*var_found)(void *, char *var, char *val))
{
    int fd, len;
    char content[4096];
    char cleaned_content[4096];
    char var[256];
    char val[256];
    char *curr, *next;
    int i = 0;

    fd = open(config_file_name, O_RDONLY);
    if (!fd) {
        printf("Can't open config file: %s\n", config_file_name);
        return -1;
    }
    len = read(fd, content, sizeof content);
    if (!len) {
        printf("Config file %s is empty\n", config_file_name);
        return -1;
    }
    close(fd);

    get_cleaned_str(content, cleaned_content);
    curr = cleaned_content;
    for (i = 0; i < 1000; i++) {
        memset (var, 0, sizeof var);
        next = parse_str(curr, var, val);
        if (next == curr)
            break;

        curr = next;
        if (var[0])
            var_found(priv, var, val);
    }

    return 0;
}


int parse_int_comma_separated(char *str, int *list)
{
    char *curr, *next;
    char buf[10];
    int cnt, val, rc;

    curr = str;
    for (cnt = 0; cnt < 100; cnt++) {
        next = strchr(curr, ',');
        if (!next) {
            memcpy(buf, curr, strlen(curr));
            val = 0;
            if (strlen(buf)) {
                rc = sscanf(buf, "%d", &val);
                if (!rc)
                    val = 0;
            }
            list[cnt] = val;
            return cnt + 1;
        } else
            next++;
        memcpy(buf, curr, next - curr);
        buf[next - curr - 1] = 0;
        curr = next;

        val = 0;
        if (strlen(buf)) {
            rc = sscanf(buf, "%d", &val);
            if (!rc)
                val = 0;
        }

        list[cnt] = val;
    }
    return 0;
}
