CC=gcc
CFLAGS = -Wall
EXEC = gpio_action
OBJ = conf.o gpio_action.o

all: $(OBJ)
	$(CC) $(CFLAGS) $(OBJ) -o $(EXEC)
clean:
	rm -rf *.o $(EXEC)

$(OBJ): conf.h
