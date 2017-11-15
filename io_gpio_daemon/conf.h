int parse_config(char *config_file_name, void *priv, void (*var_found)(void *, char *var, char *val));
int parse_int_comma_separated(char *str, int *list);
