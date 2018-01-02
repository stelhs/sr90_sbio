#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <fcntl.h>
#include <sys/time.h>
#include <sys/types.h>
#include <sys/select.h>
#include <unistd.h>
#include <signal.h>
#include <errno.h>
#include "conf.h"

#define CONFIG_FILE_NAME "/etc/sr90_sbio/triggers_input_ports.conf"

struct gpio {
    int port;
    int fd;
    int id;
};

// list GPIO ports in ascending order
static struct gpio inputs_list[30];

void input_request(int port)
{
    char buf[128];
    int fd;

    snprintf(buf, sizeof buf, "%d", port);
    fd = open("/sys/class/gpio/export", O_WRONLY);
    write(fd, buf, strlen(buf));
    close(fd);

    snprintf(buf, sizeof buf, "/sys/class/gpio/gpio%d/direction", port);
    fd = open(buf, O_WRONLY);
    write(fd, "in", 3);
    close(fd);

    snprintf(buf, sizeof buf, "/sys/class/gpio/gpio%d/edge", port);
    fd = open(buf, O_WRONLY);
    write(fd, "both", 5);
    close(fd);
}


void input_free(int port)
{
    char buf[128];
    int fd;

    snprintf(buf, sizeof buf, "%d", port);
    fd = open("/sys/class/gpio/unexport", O_WRONLY);
    write(fd, buf, strlen(buf));
    close(fd);
}


void sig_handler(int signo)
{
    struct gpio *gp;

    if (signo != SIGINT)
        return;

    printf("closing all gpio files\n");
    for (gp = inputs_list; gp->port > 0; gp++) {
        if (!gp->fd)
           continue;

        close(gp->fd);
        input_free(gp->port);
    }
    exit(0);
}


void conf_setter(void *priv, char *var, char *val)
{
    int gpio_numbers[30];
    int rc, i;
    if (strcmp(var, "input-gpio-list") == 0) {
        rc = parse_int_comma_separated(val, gpio_numbers);
        for (i = 0; i < rc; i++)
            inputs_list[i].port = gpio_numbers[i];
    }
}


int main(int argc, char **argv)
{
    int rc;
    int id, max_fd;
    char buf[128];
    struct gpio *gp;
    fd_set rfds;
    char *shell_script = argv[2];
    int value;

    if (argc < 3) {
        perror("first argument must be list of triggered GPIO numbers\n");
        perror("second argument must be path to shell script\n");
        return -1;
    }

    signal(SIGINT, sig_handler);

    memset(inputs_list, 0, sizeof inputs_list);
/*    rc = parse_config(CONFIG_FILE_NAME, NULL, conf_setter);
    if (rc < 0) {
        printf("Can't get config\n");
        return -1;
    }*/

    if (!inputs_list[0].port) {
        printf("Incorrect input-gpio-list parameter\n");
        return -1;
    }

    id = 1;
    for (gp = inputs_list; gp->port > 0; gp++) {
        gp->id = id++;
        input_free(gp->port);
        input_request(gp->port);
        snprintf(buf, sizeof buf, "/sys/class/gpio/gpio%d/value", gp->port);
        gp->fd = open(buf, O_RDONLY);
        lseek(gp->fd, 0, SEEK_SET);
        read(gp->fd, buf, sizeof buf);
    }
    
    for (;;) {
        FD_ZERO(&rfds);
        max_fd = 0;
        for (gp = inputs_list; gp->port > 0; gp++) {
            if (gp->fd > max_fd)
                max_fd = gp->fd;
            FD_SET(gp->fd, &rfds);
        }

        rc = select(max_fd + 1, NULL, NULL, &rfds, NULL);
        if (rc == -1) {
           fprintf(stderr, "select error %d", errno);
           continue;
        }

        for (gp = inputs_list; gp->port > 0; gp++) {
            if (!FD_ISSET(gp->fd , &rfds))
                continue;

            lseek(gp->fd, 0, SEEK_SET);
            read(gp->fd, buf, 3);
            sscanf(buf, "%d", &value);

            printf ("active %d = %d\n", gp->id, value);
            snprintf(buf, sizeof buf, "%s %d %d", shell_script, gp->id, value);
            system(buf);
        }
    }

    return 0;
}

