CC=gcc
CFLAGS = -Wall
EXEC = sbio_trigger
OBJ = conf.o sbio_trigger.o

all: $(OBJ)
	$(CC) $(CFLAGS) $(OBJ) -o $(EXEC)
clean:
	rm -rf *.o $(EXEC)

$(OBJ): conf.h
