#include "stdio.h"
#include <stdlib.h>
#include <mysql.h>

int main(int argc, char** argv)
{
  MYSQL *con = mysql_init(NULL);
 
  printf("%s\n", mysql_get_client_info());

  if (con == NULL) 
  {
      fprintf(stderr, "%s\n", mysql_error(con));
      exit(1);
  }

  if (mysql_real_connect(con, "localhost", "root", argv[1], 
          NULL, 0, NULL, 0) == NULL) 
  {
      fprintf(stderr, "%s\n", mysql_error(con));
      mysql_close(con);
      exit(1);
  }  

  if (mysql_query(con, "CREATE DATABASE testdb")) 
  {
      fprintf(stderr, "%s\n", mysql_error(con));
      mysql_close(con);
      exit(1);
  }

  mysql_close(con);
  return 0; 
} 
